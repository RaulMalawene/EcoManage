<?php

namespace App\Services;

use App\Enums\CategoriaLancamento;
use App\Enums\TipoLancamento;
use App\Exceptions\RegraNegocioException;
use App\Models\Material;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;

/**
 * Regista uma venda a um cliente/reciclador.
 *
 * Efeito (RF-20, RF-21), tudo dentro de UMA transacao:
 *   1. grava a venda e os itens;
 *   2. cada item SAI do stock (StockService verifica se ha material);
 *   3. o custo de cada item fica CONGELADO ao custo medio do momento;
 *   4. calcula o lucro (preco - custo) x kg;
 *   5. se paga a pronto, ENTRA no caixa.
 *
 * A diferenca-chave face a compra: a venda separa dois numeros que
 * a planilha misturava — o dinheiro que entra (caixa) e o lucro real
 * (receita menos o custo do material). E' isto que alimenta o DRE.
 */
class VendaService
{
    public function __construct(
        private StockService $stock,
        private CaixaService $caixa,
    ) {}

    /**
     * @param  array  $dados   ['data'=>, 'pessoa_id'=>, 'pago'=>bool, 'observacoes'=>]
     * @param  array  $itens   [['material_id'=>, 'quantidade_kg'=>, 'preco_kg'=>], ...]
     */
    public function registar(array $dados, array $itens, int $userId): Venda
    {
        if (empty($itens)) {
            throw new RegraNegocioException('A venda tem de ter pelo menos um item.');
        }

        return DB::transaction(function () use ($dados, $itens, $userId) {
            $pago = $dados['pago'] ?? true;

            $venda = Venda::create([
                'data' => $dados['data'] ?? now()->toDateString(),
                'pessoa_id' => $dados['pessoa_id'],
                'pago' => $pago,
                'data_recebimento' => $pago ? ($dados['data'] ?? now()->toDateString()) : null,
                'observacoes' => $dados['observacoes'] ?? null,
                'user_id' => $userId,
            ]);

            $total = 0.0;
            $custoTotal = 0.0;

            foreach ($itens as $linha) {
                $quantidade = (float) $linha['quantidade_kg'];
                $preco = (float) $linha['preco_kg'];

                if ($quantidade <= 0) {
                    throw new RegraNegocioException('A quantidade de cada item tem de ser maior que zero.');
                }

                $material = Material::findOrFail($linha['material_id']);

                // Sai do stock. Lanca RegraNegocioException se nao houver
                // material suficiente (RF-20). Devolve o movimento, de onde
                // lemos o custo medio ao qual o material saiu.
                $movimento = $this->stock->saida(
                    material: $material,
                    quantidadeKg: $quantidade,
                    origemTipo: 'Venda',
                    origemId: $venda->id,
                    userId: $userId,
                    data: $venda->data,
                );

                $custoKg = (float) $movimento->custo_kg;

                $subtotal = round($quantidade * $preco, 2);
                $custoItem = round($quantidade * $custoKg, 2);
                $lucroItem = round($subtotal - $custoItem, 2);

                $total += $subtotal;
                $custoTotal += $custoItem;

                $venda->itens()->create([
                    'material_id' => $material->id,
                    'quantidade_kg' => $quantidade,
                    'preco_kg' => $preco,
                    'custo_kg' => $custoKg,        // congelado — nao muda depois
                    'subtotal' => $subtotal,
                    'custo_total' => $custoItem,
                    'lucro' => $lucroItem,
                ]);
            }

            $total = round($total, 2);
            $custoTotal = round($custoTotal, 2);

            $venda->update([
                'total' => $total,
                'custo_total' => $custoTotal,
                'lucro' => round($total - $custoTotal, 2),
            ]);

            // So entra no caixa se foi paga a pronto. Se for a credito,
            // a receita ja conta no DRE (data da venda) mas o dinheiro
            // so entra quando o cliente pagar (registarRecebimento).
            if ($pago) {
                $this->caixa->registar(
                    tipo: TipoLancamento::Entrada,
                    categoria: CategoriaLancamento::Venda,
                    valor: $total,
                    descricao: 'Venda de material',
                    userId: $userId,
                    data: $venda->data,
                    pessoaId: $venda->pessoa_id,
                    origemTipo: 'Venda',
                    origemId: $venda->id,
                );
            }

            return $venda->load('itens.material', 'pessoa');
        });
    }

    /**
     * Regista o recebimento de uma venda que estava a credito:
     * marca como paga e da entrada no caixa.
     */
    public function registarRecebimento(Venda $venda, int $userId, $data = null): Venda
    {
        if ($venda->pago) {
            throw new RegraNegocioException('Esta venda ja estava paga.');
        }

        return DB::transaction(function () use ($venda, $userId, $data) {
            $data = $data ?? now()->toDateString();

            $venda->update(['pago' => true, 'data_recebimento' => $data]);

            $this->caixa->registar(
                tipo: TipoLancamento::Entrada,
                categoria: CategoriaLancamento::Venda,
                valor: (float) $venda->total,
                descricao: 'Recebimento de venda a credito',
                userId: $userId,
                data: $data,
                pessoaId: $venda->pessoa_id,
                origemTipo: 'Venda',
                origemId: $venda->id,
            );

            return $venda;
        });
    }
}