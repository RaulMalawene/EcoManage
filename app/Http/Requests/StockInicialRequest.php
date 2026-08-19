<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida a declaracao de stock inicial de um material: os kg que ja
 * existiam em armazem quando o sistema arrancou, e o custo estimado
 * desse material. Sem isto, a primeira venda mostraria lucro errado.
 */
class StockInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'quantidade_kg' => ['required', 'numeric', 'gt:0'],
            'custo_kg' => ['required', 'numeric', 'min:0'],
            'data' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantidade_kg.required' => 'A quantidade em kg e obrigatoria.',
            'quantidade_kg.gt' => 'A quantidade tem de ser maior que zero.',
            'custo_kg.required' => 'O custo por kg e obrigatorio.',
        ];
    }
}