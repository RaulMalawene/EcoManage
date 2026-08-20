<?php

namespace App\Http\Requests;

use App\Enums\TipoEmprestimo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida a concessao de um emprestimo (RF-22, RF-23).
 * So o valor principal e' obrigatorio; juro, vencimento e motivo
 * sao opcionais. Um adiantamento sem juro e' so juro = 0.
 */
class EmprestimoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['required', 'exists:pessoas,id'],
            'valor_principal' => ['required', 'numeric', 'gt:0'],
            'juro_valor' => ['nullable', 'numeric', 'min:0'],
            'data' => ['nullable', 'date'],
            'data_vencimento' => ['nullable', 'date', 'after_or_equal:data'],
            'motivo' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['nullable', Rule::enum(TipoEmprestimo::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'pessoa_id.required' => 'A pessoa e obrigatoria.',
            'pessoa_id.exists' => 'A pessoa indicada nao existe.',
            'valor_principal.required' => 'O valor do emprestimo e obrigatorio.',
            'valor_principal.gt' => 'O valor tem de ser maior que zero.',
            'data_vencimento.after_or_equal' => 'O vencimento nao pode ser antes da data do emprestimo.',
        ];
    }
}