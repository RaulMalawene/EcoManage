<?php

namespace App\Services;

use App\Enums\CategoriaLancamento;
use App\Enums\EstadoEmprestimo;
use App\Enums\FormaPagamento;
use App\Enums\TipoEmprestimo;
use App\Enums\TipoLancamento;
use App\Exceptions\RegraNegocioException;
use App\Models\Emprestimo;
use App\Models\Material;
use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;

/**
 * Gere emprestimos e contas a receber — o modulo prioritario (RF-22 a RF-30).
 *
 * Dois momentos:
 *   CONCEDER (registar): sai dinheiro do caixa, cria a divida.
 *   PAGAR   (registarPagamento): reduz a divida; se em dinheiro entra no
 *            caixa, se em material entra no stock.
 *
 * Regra de reparticao (definida com o cliente): cada pagamento abate
 * PRIMEIRO o juro em divida e so o resto reduz o principal. Isto separa,
 * no DRE, o juro (ganho real) do principal (dinheiro que volta).
 *
 * Um emprestimo sem juro e' so um caso de juro = 0 — sem excepcoes.
 * Um adiantamento a abater em material (tipo = adiantamento_material)
 * sai como dinheiro mas pode ser pago em material; a forma de cada
 * pagamento e' independente do tipo do emprestimo, deixando espaco
 * para pagamentos mistos (parte dinheiro, parte material).
 */
class EmprestimoService
{
    public function __construct(
        private StockService $stock,
        private CaixaService $caixa,
    ) {}

    /**
     * Concede um emprestimo/adiantamento (RF-22, RF-23).
     *
     * @param array $dados [
     *   'pessoa_id'=>, 'valor_principal'=>, 'juro_valor'=>?, 'data'=>?,
     *   'data_vencimento'=>?, 'motivo'=>?, 'tipo'=>? (dinheiro|adiantamento_material)
     * ]
     */
    public function registar(array $dados, int $userId): Emprestimo
    {
        $principal = (float) $dados['valor_principal'];
        $juro = (float) ($dados['juro_valor'] ?? 0);

        if ($principal <= 0) {
            throw new RegraNegocioException('O valor do emprestimo tem de ser maior que zero.');
        }
        if ($juro < 0) {
            throw new RegraNegocioException('O juro nao pode ser negativo.');
        }

        return DB::transaction(function () use ($dados, $principal, $juro, $userId) {
            $total = round($principal + $juro, 2);
            $data = $dados['data'] ?? now()->toDateString();
            $tipo = $dados['tipo'] ?? TipoEmprestimo::Dinheiro->value;

            $emprestimo = Emprestimo::create([
                'pessoa_id' => $dados['pessoa_id'],
                'data' => $data,
                'valor_principal' => round($principal, 2),
                'juro_valor' => round($juro, 2),
                'valor_total' => $total,
                'saldo_devedor' => $total,        // comeca a dever tudo
                'data_vencimento' => $dados['data_vencimento'] ?? null,
                'motivo' => $dados['motivo'] ?? null,
                'tipo' => $tipo,
                'estado' => EstadoEmprestimo::EmDia,
                'user_id' => $userId,
            ]);

            // Conceder um emprestimo faz SAIR dinheiro do caixa (RF-30).
            // Sai so o principal — o juro ainda nao existe, e' expectativa.
            $this->caixa->registar(
                tipo: TipoLancamento::Saida,
                categoria: CategoriaLancamento::Emprestimo,
                valor: round($principal, 2),
                descricao: 'Emprestimo concedido',
                userId: $userId,
                data: $data,
                pessoaId: $emprestimo->pessoa_id,
                origemTipo: 'Emprestimo',
                origemId: $emprestimo->id,
            );

            return $emprestimo->load('pessoa');
        });
    }

    /**
     * Regista um pagamento, total ou parcial (RF-24).
     *
     * @param array $dados [
     *   'valor'=>, 'data'=>?, 'forma'=>? (dinheiro|material),
     *   'material_id'=>? , 'quantidade_kg'=>?   (quando forma = material)
     * ]
     */
    public function registarPagamento(Emprestimo $emprestimo, array $dados, int $userId): Pagamento
    {
        $valor = (float) $dados['valor'];
        $forma = $dados['forma'] ?? FormaPagamento::Dinheiro->value;

        if ($valor <= 0) {
            throw new RegraNegocioException('O valor do pagamento tem de ser maior que zero.');
        }

        return DB::transaction(function () use ($emprestimo, $dados, $valor, $forma, $userId) {
            // Bloqueia a linha para que dois pagamentos simultaneos nao
            // reduzam o saldo devedor a partir do mesmo valor de partida.
            $emprestimo = Emprestimo::lockForUpdate()->findOrFail($emprestimo->id);

            $saldo = (float) $emprestimo->saldo_devedor;

            if ($saldo <= 0) {
                throw new RegraNegocioException('Este emprestimo ja esta liquidado.');
            }

            // Nao se pode pagar mais do que se deve.
            if ($valor > $saldo + 0.0001) {
                throw new RegraNegocioException(
                    'O pagamento (' . number_format($valor, 2, ',', '.') . ' MT) '
                    . 'excede o saldo devedor (' . number_format($saldo, 2, ',', '.') . ' MT).'
                );
            }

            $data = $dados['data'] ?? now()->toDateString();

            // Reparticao: juro primeiro, principal depois.
            // O juro ainda por receber e' a diferenca entre o juro total
            // e o juro ja pago em pagamentos anteriores.
            $juroJaPago = (float) $emprestimo->pagamentos()->sum('valor_juro');
            $juroEmDivida = max(0.0, (float) $emprestimo->juro_valor - $juroJaPago);

            $parteJuro = min($valor, $juroEmDivida);
            $partePrincipal = round($valor - $parteJuro, 2);
            $parteJuro = round($parteJuro, 2);

            // Preparar campos de material, se for o caso.
            $materialId = null;
            $quantidadeKg = null;

            if ($forma === FormaPagamento::Material->value) {
                if (empty($dados['material_id']) || empty($dados['quantidade_kg'])) {
                    throw new RegraNegocioException(
                        'Pagamento em material exige o material e a quantidade em kg.'
                    );
                }
                $materialId = $dados['material_id'];
                $quantidadeKg = (float) $dados['quantidade_kg'];
            }

            $pagamento = Pagamento::create([
                'emprestimo_id' => $emprestimo->id,
                'data' => $data,
                'valor' => round($valor, 2),
                'valor_juro' => $parteJuro,
                'valor_principal' => $partePrincipal,
                'forma' => $forma,
                'material_id' => $materialId,
                'quantidade_kg' => $quantidadeKg,
                'user_id' => $userId,
            ]);

            // Reduz o saldo devedor.
            $novoSaldo = round($saldo - $valor, 2);
            $emprestimo->saldo_devedor = $novoSaldo;
            $emprestimo->estado = $this->calcularEstado($emprestimo, $novoSaldo);
            $emprestimo->save();

            // Efeito no caixa ou no stock, conforme a forma.
            if ($forma === FormaPagamento::Dinheiro->value) {
                // Dinheiro que volta ENTRA no caixa (RF-30).
                $this->caixa->registar(
                    tipo: TipoLancamento::Entrada,
                    categoria: CategoriaLancamento::Recebimento,
                    valor: round($valor, 2),
                    descricao: 'Recebimento de divida',
                    userId: $userId,
                    data: $data,
                    pessoaId: $emprestimo->pessoa_id,
                    origemTipo: 'Pagamento',
                    origemId: $pagamento->id,
                );
            } else {
                // Pago em material: entra sucata no stock, ao custo
                // implicito (valor pago / kg entregues). Nao mexe no caixa —
                // nao houve dinheiro, houve troca por material.
                $custoKg = $quantidadeKg > 0 ? $valor / $quantidadeKg : 0;

                $this->stock->entrada(
                    material: Material::findOrFail($materialId),
                    quantidadeKg: $quantidadeKg,
                    custoKg: $custoKg,
                    origemTipo: 'Pagamento',
                    origemId: $pagamento->id,
                    userId: $userId,
                    data: $data,
                    observacoes: 'Abatimento de divida em material',
                );
            }

            return $pagamento;
        });
    }

    /**
     * Recalcula o estado de todos os emprestimos por liquidar face a data
     * de hoje. Serve para o aviso de vencimento (RF-28): um emprestimo
     * "em dia" passa a "vencido" quando a data de vencimento chega, mesmo
     * sem ninguem tocar nele. Deve correr uma vez por dia (agendado).
     */
    public function actualizarEstados(): int
    {
        $afectados = 0;

        Emprestimo::porLiquidar()->each(function (Emprestimo $e) use (&$afectados) {
            $novo = $this->calcularEstado($e, (float) $e->saldo_devedor);
            if ($novo !== $e->estado) {
                $e->estado = $novo;
                $e->save();
                $afectados++;
            }
        });

        return $afectados;
    }

    /** Estado correcto face ao saldo e a data (regra do capitulo 5). */
    private function calcularEstado(Emprestimo $emprestimo, float $saldo): EstadoEmprestimo
    {
        if ($saldo <= 0.0) {
            return EstadoEmprestimo::Liquidado;
        }

        if ($emprestimo->data_vencimento && $emprestimo->data_vencimento->isPast()) {
            return EstadoEmprestimo::Vencido;
        }

        return EstadoEmprestimo::EmDia;
    }
}