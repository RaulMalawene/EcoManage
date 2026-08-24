<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'preco_compra_kg' => (float) $this->preco_compra_kg,
            'preco_venda_kg' => (float) $this->preco_venda_kg,
            'stock_kg' => (float) $this->stock_kg,
            'total_quebras_kg' => (float) $this->total_quebras_kg,
            'custo_medio_kg' => (float) $this->custo_medio_kg,
            'limite_alerta_kg' => $this->limite_alerta_kg !== null ? (float) $this->limite_alerta_kg : null,

            // Valor imobilizado neste material (RF-14): kg x custo medio.
            'valor_stock' => round((float) $this->stock_kg * (float) $this->custo_medio_kg, 2),

            // Esta em alerta de venda? (RF-15)
            'em_alerta' => $this->limite_alerta_kg !== null
                && (float) $this->stock_kg >= (float) $this->limite_alerta_kg,

            'activo' => $this->activo,
        ];
    }
}