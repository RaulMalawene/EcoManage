<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmprestimoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'pessoa_id' => $this->pessoa_id,
            'pessoa' => $this->whenLoaded('pessoa', fn () => $this->pessoa->nome),

            'valor_principal' => (float) $this->valor_principal,
            'juro_valor' => (float) $this->juro_valor,
            'valor_total' => (float) $this->valor_total,       // principal + juro
            'saldo_devedor' => (float) $this->saldo_devedor,   // quanto falta receber
            'total_pago' => round((float) $this->valor_total - (float) $this->saldo_devedor, 2),

            'data_vencimento' => $this->data_vencimento?->toDateString(),
            'motivo' => $this->motivo,
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),

            // Preenchidos so quando tipo = material_emprestado.
            'material_id' => $this->material_id,
            'material' => $this->whenLoaded('material', fn () => $this->material?->nome),
            'quantidade_kg' => $this->quantidade_kg !== null ? (float) $this->quantidade_kg : null,

            'estado' => $this->estado->value,
            'estado_rotulo' => $this->estado->rotulo(),

            'pagamentos' => PagamentoResource::collection($this->whenLoaded('pagamentos')),
            'criado_em' => $this->created_at?->toDateTimeString(),
        ];
    }
}