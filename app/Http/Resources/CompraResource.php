<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'pessoa_id' => $this->pessoa_id,
            'fornecedor' => $this->whenLoaded('pessoa', fn () => $this->pessoa->nome),
            'total' => (float) $this->total,
            'observacoes' => $this->observacoes,
            'itens' => ItemCompraResource::collection($this->whenLoaded('itens')),
            'num_itens' => $this->whenLoaded('itens', fn () => $this->itens->count()),
            'criado_em' => $this->created_at?->toDateTimeString(),
        ];
    }
}