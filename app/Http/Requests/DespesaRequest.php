<?php

namespace App\Http\Requests;

use App\Enums\GrupoDre;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida o registo de uma despesa (RF-06).
 * So valor, categoria e descricao sao obrigatorios. A competencia, se
 * nao vier, iguala-se a data de pagamento no servico.
 */
class DespesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'categoria' => ['required', 'string', 'max:50'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'gt:0'],
            'data' => ['nullable', 'date'],
            'data_competencia' => ['nullable', 'date'],
            'grupo_dre' => ['nullable', Rule::enum(GrupoDre::class)],
            'pessoa_id' => ['nullable', 'exists:pessoas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria.required' => 'A categoria da despesa e obrigatoria.',
            'descricao.required' => 'A descricao e obrigatoria.',
            'valor.required' => 'O valor e obrigatorio.',
            'valor.gt' => 'O valor tem de ser maior que zero.',
        ];
    }
}