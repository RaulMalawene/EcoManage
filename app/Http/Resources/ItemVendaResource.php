<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemVendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'material_nome' => $this->whenLoaded('material', fn () => $this->material->nome),
            'quantidade_kg' => (float) $this->quantidade_kg,
            'preco_kg' => (float) $this->preco_kg,
            'custo_kg' => (float) $this->custo_kg,   // custo medio congelado no momento
            'subtotal' => (float) $this->subtotal,   // quanto rendeu
            'custo_total' => (float) $this->custo_total,
            'lucro' => (float) $this->lucro,
        ];
    }
}