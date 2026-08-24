<?php

namespace App\Services;

use App\Enums\TipoLancamento;
use App\Exceptions\RegraNegocioException;
use App\Models\Material;
use App\Models\MovimentoStock;
use Illuminate\Support\Facades\DB;

/**
 * Gere o armazem: cada entrada ou saida de material passa por aqui.
 *
 * Mantem duas coisas em sincronia:
 *   - materiais.stock_kg        (quanto ha agora)
 *   - materiais.custo_medio_kg  (a que custo medio)
 * e grava um registo em movimentos_stock para cada operacao, para
 * que o historico e o grafico de stock ao longo do tempo existam.
 *
 * O custo medio e' PONDERADO: quando entra material a um preco novo,
 * o custo medio recalcula-se misturando o stock antigo com o novo.
 * As saidas nao mexem no custo medio — saem ao custo que ja existia.
 */
class StockService
{
    /**
     * Entrada de material (compra, adiantamento pago em material,
     * stock inicial ou ajuste positivo).
     *
     * Formula do custo medio ponderado:
     *   novo_custo = (kg_antigo * custo_antigo + kg_entra * custo_entra)
     *                / (kg_antigo + kg_entra)
     */
    public function entrada(
        Material $material,
        float $quantidadeKg,
        float $custoKg,
        string $origemTipo,
        ?int $origemId,
        int $userId,
        $data = null,
        ?string $observacoes = null,
    ): MovimentoStock {
        if ($quantidadeKg <= 0) {
            throw new RegraNegocioException('A quantidade de entrada tem de ser maior que zero.');
        }

        return DB::transaction(function () use (
            $material, $quantidadeKg, $custoKg, $origemTipo, $origemId, $userId, $data, $observacoes
        ) {
            // Bloqueia a linha do material ate ao fim da transacao, para
            // que duas entradas em simultaneo nao corrompam o custo medio.
            $material = Material::lockForUpdate()->findOrFail($material->id);

            $stockAntes = (float) $material->stock_kg;
            $custoAntes = (float) $material->custo_medio_kg;

            $stockDepois = $stockAntes + $quantidadeKg;

            // Media ponderada. Se o stock estava a zero, o novo custo
            // e' simplesmente o custo desta entrada.
            $custoDepois = $stockDepois > 0
                ? (($stockAntes * $custoAntes) + ($quantidadeKg * $custoKg)) / $stockDepois
                : $custoKg;

            $material->stock_kg = round($stockDepois, 3);
            $material->custo_medio_kg = round($custoDepois, 4);
            $material->save();

            return MovimentoStock::create([
                'data' => $data ?? now()->toDateString(),
                'material_id' => $material->id,
                'tipo' => TipoLancamento::Entrada,
                'origem_tipo' => $origemTipo,
                'origem_id' => $origemId,
                'quantidade_kg' => round($quantidadeKg, 3),
                'custo_kg' => round($custoKg, 4),
                'stock_apos_kg' => round($stockDepois, 3),
                'custo_medio_apos_kg' => round($custoDepois, 4),
                'observacoes' => $observacoes,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Saida de material (venda ou ajuste negativo). Devolve o custo
     * medio ao qual o material saiu — e' este valor que a venda grava
     * como custo_kg do item, congelando o lucro daquela venda.
     */
    public function saida(
        Material $material,
        float $quantidadeKg,
        string $origemTipo,
        ?int $origemId,
        int $userId,
        $data = null,
        ?string $observacoes = null,
    ): MovimentoStock {
        if ($quantidadeKg <= 0) {
            throw new RegraNegocioException('A quantidade de saida tem de ser maior que zero.');
        }

        return DB::transaction(function () use (
            $material, $quantidadeKg, $origemTipo, $origemId, $userId, $data, $observacoes
        ) {
            $material = Material::lockForUpdate()->findOrFail($material->id);

            $stockAntes = (float) $material->stock_kg;

            // RF-20: nao se pode vender mais do que existe.
            if ($quantidadeKg > $stockAntes + 0.0001) {
                throw new RegraNegocioException(
                    "Stock insuficiente de {$material->nome}: existem "
                    . number_format($stockAntes, 3, ',', '.') . ' kg, '
                    . 'foram pedidos ' . number_format($quantidadeKg, 3, ',', '.') . ' kg.'
                );
            }

            $custoSaida = (float) $material->custo_medio_kg;
            $stockDepois = $stockAntes - $quantidadeKg;

            // A saida nao altera o custo medio; so baixa a quantidade.
            $material->stock_kg = round($stockDepois, 3);
            $material->save();

            return MovimentoStock::create([
                'data' => $data ?? now()->toDateString(),
                'material_id' => $material->id,
                'tipo' => TipoLancamento::Saida,
                'origem_tipo' => $origemTipo,
                'origem_id' => $origemId,
                'quantidade_kg' => round($quantidadeKg, 3),
                'custo_kg' => round($custoSaida, 4),
                'stock_apos_kg' => round($stockDepois, 3),
                'custo_medio_apos_kg' => round($custoSaida, 4),
                'observacoes' => $observacoes,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Quebra de stock: kg perdidos (humidade, danos, manuseamento, etc.)
     * que saem do armazem sem terem sido vendidos. E' uma saida como
     * outra qualquer — reduz stock_kg e nao mexe no custo medio — mas
     * fica marcada com origem "Quebra" (para nao entrar como receita
     * nos relatorios) e soma-se a materiais.total_quebras_kg, para o
     * dono ver de relance quanto cada material tem perdido.
     */
    public function quebra(
        Material $material,
        float $quantidadeKg,
        int $userId,
        $data = null,
        ?string $observacoes = null,
    ): MovimentoStock {
        return DB::transaction(function () use ($material, $quantidadeKg, $userId, $data, $observacoes) {
            $movimento = $this->saida(
                material: $material,
                quantidadeKg: $quantidadeKg,
                origemTipo: 'Quebra',
                origemId: null,
                userId: $userId,
                data: $data,
                observacoes: $observacoes ?? 'Quebra de stock',
            );

            Material::whereKey($material->id)
                ->increment('total_quebras_kg', round($quantidadeKg, 3));

            return $movimento;
        });
    }
}