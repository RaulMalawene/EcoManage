<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        // Ao editar, o material vem na rota (?material). Ao criar, e' null.
        $materialId = $this->route('material')?->id;

        return [
            'nome' => [
                'required', 'string', 'max:255',
                // Nome unico, mas ignora o proprio registo ao editar.
                Rule::unique('materiais', 'nome')->ignore($materialId),
            ],
            'preco_compra_kg' => ['required', 'numeric', 'min:0'],
            'preco_venda_kg' => ['required', 'numeric', 'min:0'],
            'limite_alerta_kg' => ['nullable', 'numeric', 'min:0'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do material e obrigatorio.',
            'nome.unique' => 'Ja existe um material com esse nome.',
            'preco_compra_kg.required' => 'O preco de compra por kg e obrigatorio.',
            'preco_venda_kg.required' => 'O preco de venda por kg e obrigatorio.',
        ];
    }
}