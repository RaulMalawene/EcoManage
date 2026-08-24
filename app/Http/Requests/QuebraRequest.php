<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o registo de uma quebra de stock: kg perdidos (humidade,
 * danos, manuseamento, etc.) que saem do armazem sem terem sido
 * vendidos. Usa o mesmo StockService que a venda, mas com origem
 * "Quebra" — para nao entrar como receita nos relatorios.
 */
class QuebraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'quantidade_kg' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantidade_kg.required' => 'A quantidade perdida em kg e obrigatoria.',
            'quantidade_kg.gt' => 'A quantidade tem de ser maior que zero.',
        ];
    }
}
