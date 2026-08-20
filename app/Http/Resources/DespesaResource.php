<?php

namespace App\Http\Resources;

use App\Enums\GrupoDre;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DespesaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data?->toDateString(),
            'data_competencia' => $this->data_competencia?->toDateString(),
            'categoria' => $this->categoria,
            'grupo_dre' => $this->grupo_dre->value,
            'grupo_dre_rotulo' => $this->grupo_dre->rotulo(),
            'descricao' => $this->descricao,
            'valor' => (float) $this->valor,
            'pessoa' => $this->whenLoaded('pessoa', fn () => $this->pessoa?->nome),
            'criado_em' => $this->created_at?->toDateTimeString(),
        ];
    }
}