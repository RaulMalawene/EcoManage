<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida os dados do login. Um Form Request separa a validacao do
 * controller: se os dados nao passarem, o Laravel devolve 422 com os
 * erros automaticamente, e o controller so corre com dados ja validos.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // qualquer um pode tentar entrar
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'O nome de utilizador e obrigatorio.',
            'password.required' => 'A palavra-passe e obrigatoria.',
        ];
    }
}