<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            // whenLoaded: so inclui o nome se a relacao foi carregada,
            // evitando uma query extra por item.
            'material_nome' => $this->whenLoaded('material', fn () => $this->material->nome),
            'quantidade_kg' => (float) $this->quantidade_kg,
            'preco_kg' => (float) $this->preco_kg,
            'subtotal' => (float) $this->subtotal,
        ];
    }
}