<?php

namespace App\Http\Requests;

use App\Enums\FormaPagamento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida um pagamento de emprestimo (RF-24).
 * O cliente envia so o VALOR total; a reparticao juro/principal e' feita
 * pelo servico. Se a forma for material, exige o material e os kg.
 */
class PagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0'],
            'data' => ['nullable', 'date'],
            'forma' => ['nullable', Rule::enum(FormaPagamento::class)],

            // Obrigatorios apenas quando forma = material.
            'material_id' => ['required_if:forma,material', 'exists:materiais,id'],
            'quantidade_kg' => ['required_if:forma,material', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'O valor do pagamento e obrigatorio.',
            'valor.gt' => 'O valor tem de ser maior que zero.',
            'material_id.required_if' => 'Pagamento em material exige indicar o material.',
            'quantidade_kg.required_if' => 'Pagamento em material exige a quantidade em kg.',
        ];
    }
}