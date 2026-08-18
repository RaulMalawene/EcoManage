<?php

namespace App\Http\Requests;

use App\Enums\TipoPessoa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida a criacao e edicao de pessoas (RF-31). O mesmo Request serve
 * para as duas: o metodo isMethod('post') distingue criar de editar
 * quando a regra precisa de ser diferente (aqui nao precisa).
 */
class PessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // So perfis com permissao de escrita criam/editam (RF-02).
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::enum(TipoPessoa::class)],
            'telefone' => ['nullable', 'string', 'max:30'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome e obrigatorio.',
            'tipo.required' => 'O tipo e obrigatorio.',
            'tipo.Illuminate\Validation\Rules\Enum' => 'O tipo tem de ser fornecedor, cliente, devedor ou misto.',
        ];
    }
}