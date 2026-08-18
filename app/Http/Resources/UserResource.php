<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Define como um utilizador aparece nas respostas da API. Nunca expoe
 * a palavra-passe nem campos internos — so o que o cliente precisa.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'perfil' => $this->perfil->value,
            'perfil_rotulo' => $this->perfil->rotulo(),
            'activo' => $this->activo,
        ];
    }
}