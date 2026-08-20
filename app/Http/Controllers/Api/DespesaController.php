<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\DespesaRequest;
use App\Http\Resources\DespesaResource;
use App\Models\Despesa;
use App\Services\DespesaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registo e consulta de despesas (Modulo 6, RF-06).
 * O store delega no DespesaService, que grava a despesa e da a saida
 * no caixa numa so transacao.
 */
class DespesaController extends Controller
{
    use RespostaApi;

    public function index(Request $request): JsonResponse
    {
        $query = Despesa::with('pessoa');

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->string('categoria'));
        }

        if ($request->filled('grupo_dre')) {
            $query->where('grupo_dre', $request->string('grupo_dre'));
        }

        // Filtro por data de pagamento (para o caixa).
        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        $despesas = $query->orderByDesc('data')->orderByDesc('id')->paginate(20);

        $total = (clone $query)->sum('valor');

        return $this->ok([
            'itens' => DespesaResource::collection($despesas),
            'resumo' => ['total' => round((float) $total, 2)],
            'paginacao' => [
                'total' => $despesas->total(),
                'pagina' => $despesas->currentPage(),
                'por_pagina' => $despesas->perPage(),
                'ultima_pagina' => $despesas->lastPage(),
            ],
        ]);
    }

    public function show(Despesa $despesa): JsonResponse
    {
        return $this->ok(new DespesaResource($despesa->load('pessoa')));
    }

    public function store(DespesaRequest $request, DespesaService $servico): JsonResponse
    {
        $despesa = $servico->registar($request->validated(), $request->user()->id);

        return $this->criado(
            new DespesaResource($despesa->load('pessoa')),
            'Despesa registada com sucesso.'
        );
    }
}