<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o registo de uma compra e a sua lista de itens (RF-16, RF-17).
 *
 * A validacao de 'itens' e' aninhada: 'itens' tem de ser um array com
 * pelo menos uma linha, e cada linha tem material, quantidade e preco.
 * O 'exists' garante que o material existe e esta activo antes sequer
 * de o servico correr.
 */
class CompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->podeEscrever();
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['required', 'exists:pessoas,id'],
            'data' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string', 'max:1000'],

            'itens' => ['required', 'array', 'min:1'],
            'itens.*.material_id' => ['required', 'exists:materiais,id'],
            'itens.*.quantidade_kg' => ['required', 'numeric', 'gt:0'],
            'itens.*.preco_kg' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pessoa_id.required' => 'O fornecedor e obrigatorio.',
            'pessoa_id.exists' => 'O fornecedor indicado nao existe.',
            'itens.required' => 'A compra tem de ter pelo menos um item.',
            'itens.min' => 'A compra tem de ter pelo menos um item.',
            'itens.*.material_id.required' => 'Cada item tem de indicar o material.',
            'itens.*.material_id.exists' => 'Um dos materiais indicados nao existe.',
            'itens.*.quantidade_kg.gt' => 'A quantidade de cada item tem de ser maior que zero.',
            'itens.*.preco_kg.required' => 'Cada item tem de indicar o preco por kg.',
        ];
    }
}