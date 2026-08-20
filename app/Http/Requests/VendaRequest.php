<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o registo de uma venda e os seus itens (RF-19, RF-20).
 * Estrutura igual a' da compra: cabecalho + lista de itens.
 *
 * O campo 'pago' distingue venda a pronto (dinheiro entra ja no caixa)
 * de venda a credito (a receita conta, mas o dinheiro entra depois).
 * Se nao for enviado, assume-se pago = true (pronto pagamento).
 */
class VendaRequest extends FormRequest
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
            'pago' => ['sometimes', 'boolean'],
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
            'pessoa_id.required' => 'O cliente e obrigatorio.',
            'pessoa_id.exists' => 'O cliente indicado nao existe.',
            'itens.required' => 'A venda tem de ter pelo menos um item.',
            'itens.min' => 'A venda tem de ter pelo menos um item.',
            'itens.*.material_id.required' => 'Cada item tem de indicar o material.',
            'itens.*.material_id.exists' => 'Um dos materiais indicados nao existe.',
            'itens.*.quantidade_kg.gt' => 'A quantidade de cada item tem de ser maior que zero.',
            'itens.*.preco_kg.required' => 'Cada item tem de indicar o preco por kg.',
        ];
    }
}