<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimentoStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'origem_tipo' => $this->origem_tipo,
            'origem_id' => $this->origem_id,
            'quantidade_kg' => (float) $this->quantidade_kg,
            'custo_kg' => (float) $this->custo_kg,
            // Valor perdido nesta quebra: kg x custo medio a que saiu.
            'valor' => round((float) $this->quantidade_kg * (float) $this->custo_kg, 2),
            'stock_apos_kg' => (float) $this->stock_apos_kg,
            'observacoes' => $this->observacoes,
            'utilizador' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}
