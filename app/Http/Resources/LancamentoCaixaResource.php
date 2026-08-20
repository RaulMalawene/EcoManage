<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LancamentoCaixaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'categoria' => $this->categoria->value,
            'categoria_rotulo' => $this->categoria->rotulo(),
            'descricao' => $this->descricao,
            'valor' => (float) $this->valor,
            // Positivo se entrada, negativo se saida — util para o frontend.
            'valor_com_sinal' => round((float) $this->valor * $this->tipo->sinal(), 2),
            'saldo_apos' => (float) $this->saldo_apos,   // a coluna TOTAL da planilha
            'pessoa' => $this->whenLoaded('pessoa', fn () => $this->pessoa?->nome),
            'origem_tipo' => $this->origem_tipo,
            'origem_id' => $this->origem_id,
        ];
    }
}