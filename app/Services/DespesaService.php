<?php

namespace App\Services;

use App\Enums\CategoriaLancamento;
use App\Enums\TipoLancamento;
use App\Exceptions\RegraNegocioException;
use App\Models\Despesa;
use Illuminate\Support\Facades\DB;

/**
 * Regista uma despesa de funcionamento (renda, salarios, transporte...).
 *
 * Efeito, dentro de UMA transacao:
 *   1. grava a despesa;
 *   2. faz SAIR o dinheiro do caixa na data do pagamento.
 *
 * Duas datas distintas (ambas gravadas):
 *   - data            = quando o dinheiro saiu (conta para o CAIXA)
 *   - data_competencia = a que mes o gasto pertence (conta para o DRE)
 * Sao iguais na maioria dos casos; divergem no salario de Junho pago
 * em Julho, por exemplo.
 *
 * grupo_dre decide se (e onde) a despesa entra no lucro:
 *   - operacional     -> despesas operacionais (renda, salarios...)
 *   - impostos_outros -> abaixo do resultado operacional
 *   - nao_operacional -> NAO entra no DRE (ex.: compra de equipamento)
 */
class DespesaService
{
    public function __construct(private CaixaService $caixa) {}

    public function registar(array $dados, int $userId): Despesa
    {
        $valor = (float) $dados['valor'];

        if ($valor <= 0) {
            throw new RegraNegocioException('O valor da despesa tem de ser maior que zero.');
        }

        return DB::transaction(function () use ($dados, $valor, $userId) {
            $data = $dados['data'] ?? now()->toDateString();

            $despesa = Despesa::create([
                'data' => $data,
                // Se nao vier competencia, assume-se igual a data de pagamento.
                'data_competencia' => $dados['data_competencia'] ?? $data,
                'categoria' => $dados['categoria'],
                'grupo_dre' => $dados['grupo_dre'] ?? 'operacional',
                'descricao' => $dados['descricao'],
                'valor' => round($valor, 2),
                'pessoa_id' => $dados['pessoa_id'] ?? null,
                'user_id' => $userId,
            ]);

            // A despesa faz SAIR dinheiro do caixa (RF-06).
            $this->caixa->registar(
                tipo: TipoLancamento::Saida,
                categoria: CategoriaLancamento::Despesa,
                valor: round($valor, 2),
                descricao: $dados['descricao'],
                userId: $userId,
                data: $data,
                pessoaId: $dados['pessoa_id'] ?? null,
                origemTipo: 'Despesa',
                origemId: $despesa->id,
            );

            return $despesa;
        });
    }
}