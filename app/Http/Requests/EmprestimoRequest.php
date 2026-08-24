<?php

namespace App\Http\Requests;

use App\Enums\TipoEmprestimo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida a concessao de um emprestimo (RF-22, RF-23).
 * So o valor principal e' obrigatorio; juro, vencimento e motivo
 * sao opcionais. Um adiantamento sem juro e' so juro = 0.
 *
 * Quando tipo = material_emprestado, o emprestimo sai directamente do
 * stock (kg de material), em vez de dinheiro do caixa — por isso exige
 * tambem o material e a quantidade em kg. O valor_principal continua
 * obrigatorio mesmo nesse caso: e' o staff que define o valor em MT da
 * divida criada, tal como ja acontece no pagamento em material.
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

            // Obrigatorios apenas quando tipo = material_emprestado.
            'material_id' => ['required_if:tipo,material_emprestado', 'exists:materiais,id'],
            'quantidade_kg' => ['required_if:tipo,material_emprestado', 'numeric', 'gt:0'],
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
            'material_id.required_if' => 'Emprestimo em material exige indicar o material.',
            'quantidade_kg.required_if' => 'Emprestimo em material exige a quantidade em kg.',
        ];
    }
}