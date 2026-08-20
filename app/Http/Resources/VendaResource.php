<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'pessoa_id' => $this->pessoa_id,
            'cliente' => $this->whenLoaded('pessoa', fn () => $this->pessoa->nome),

            'total' => (float) $this->total,           // receita
            'custo_total' => (float) $this->custo_total, // custo dos materiais
            'lucro' => (float) $this->lucro,           // total - custo

            // Margem em %: quanto do que recebeste foi lucro.
            'margem_pct' => (float) $this->total > 0
                ? round((float) $this->lucro / (float) $this->total * 100, 1)
                : 0.0,

            'pago' => $this->pago,
            'data_recebimento' => $this->data_recebimento?->toDateString(),

            'observacoes' => $this->observacoes,
            'itens' => ItemVendaResource::collection($this->whenLoaded('itens')),
            'num_itens' => $this->whenLoaded('itens', fn () => $this->itens->count()),
            'criado_em' => $this->created_at?->toDateTimeString(),
        ];
    }
}