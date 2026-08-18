<?php

namespace App\Services;

use App\Enums\CategoriaLancamento;
use App\Enums\TipoLancamento;
use App\Exceptions\RegraNegocioException;
use App\Models\Compra;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

/**
 * Regista uma compra de sucata a um fornecedor/biscateiro.
 *
 * Efeito (RF-17), tudo dentro de UMA transacao:
 *   1. grava a compra e os seus itens;
 *   2. cada item ENTRA no stock (StockService recalcula o custo medio);
 *   3. o total SAI do caixa (CaixaService).
 *
 * Se qualquer passo falhar, a transacao desfaz tudo — nunca fica
 * stock a subir sem o dinheiro a descer, nem o contrario.
 */
class CompraService
{
    public function __construct(
        private StockService $stock,
        private CaixaService $caixa,
    ) {}

    /**
     * @param  array  $dados   ['data' => ..., 'pessoa_id' => ..., 'observacoes' => ...]
     * @param  array  $itens   [['material_id' => ..., 'quantidade_kg' => ..., 'preco_kg' => ...], ...]
     */
    public function registar(array $dados, array $itens, int $userId): Compra
    {
        if (empty($itens)) {
            throw new RegraNegocioException('A compra tem de ter pelo menos um item.');
        }

        return DB::transaction(function () use ($dados, $itens, $userId) {
            $compra = Compra::create([
                'data' => $dados['data'] ?? now()->toDateString(),
                'pessoa_id' => $dados['pessoa_id'],
                'observacoes' => $dados['observacoes'] ?? null,
                'user_id' => $userId,
            ]);

            $total = 0.0;

            foreach ($itens as $linha) {
                $quantidade = (float) $linha['quantidade_kg'];
                $preco = (float) $linha['preco_kg'];

                if ($quantidade <= 0) {
                    throw new RegraNegocioException('A quantidade de cada item tem de ser maior que zero.');
                }

                $subtotal = round($quantidade * $preco, 2);
                $total += $subtotal;

                $material = Material::findOrFail($linha['material_id']);

                // Grava o item da compra.
                $compra->itens()->create([
                    'material_id' => $material->id,
                    'quantidade_kg' => $quantidade,
                    'preco_kg' => $preco,
                    'subtotal' => $subtotal,
                ]);

                // Entra no stock ao preco pago — recalcula o custo medio.
                $this->stock->entrada(
                    material: $material,
                    quantidadeKg: $quantidade,
                    custoKg: $preco,
                    origemTipo: 'Compra',
                    origemId: $compra->id,
                    userId: $userId,
                    data: $compra->data,
                );
            }

            $total = round($total, 2);
            $compra->update(['total' => $total]);

            // Sai do caixa (RF-17). Uma compra reduz o dinheiro mas
            // NAO e' custo no DRE — virou stock.
            $this->caixa->registar(
                tipo: TipoLancamento::Saida,
                categoria: CategoriaLancamento::Compra,
                valor: $total,
                descricao: 'Compra de material',
                userId: $userId,
                data: $compra->data,
                pessoaId: $compra->pessoa_id,
                origemTipo: 'Compra',
                origemId: $compra->id,
            );

            return $compra->load('itens.material', 'pessoa');
        });
    }
}