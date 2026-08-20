<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'valor' => (float) $this->valor,
            'valor_juro' => (float) $this->valor_juro,           // parte que foi ganho
            'valor_principal' => (float) $this->valor_principal, // parte que voltou
            'forma' => $this->forma->value,
            'forma_rotulo' => $this->forma->rotulo(),
            'material_id' => $this->material_id,
            'material_nome' => $this->whenLoaded('material', fn () => $this->material?->nome),
            'quantidade_kg' => $this->quantidade_kg !== null ? (float) $this->quantidade_kg : null,
        ];
    }
}