<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PessoaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'telefone' => $this->telefone,
            'observacoes' => $this->observacoes,
            'activo' => $this->activo,

            // Saldo em divida — so incluido quando foi carregado, para
            // nao correr a query em listagens grandes.
            'saldo_devedor' => $this->when(
                $request->boolean('com_saldo'),
                fn () => $this->saldo_devedor
            ),

            'criado_em' => $this->created_at?->toDateTimeString(),
        ];
    }
}