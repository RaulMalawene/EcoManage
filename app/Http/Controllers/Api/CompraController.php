<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\FormataPeriodo;
use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompraRequest;
use App\Http\Resources\CompraResource;
use App\Models\Compra;
use App\Services\CompraService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registo e consulta de compras (Modulo 4, RF-16 a RF-18).
 *
 * O store delega no CompraService, que dentro de uma transacao grava a
 * compra, da entrada no stock (recalculando o custo medio) e regista a
 * saida no caixa. O controller so valida e formata.
 *
 * Nao ha update nem destroy: uma compra registada mexeu em stock e caixa,
 * por isso corrige-se por anulacao (tratada a parte), nunca por edicao.
 */
class CompraController extends Controller
{
    use RespostaApi, FormataPeriodo;

    public function index(Request $request): JsonResponse
    {
        // Eager loading (with) evita o problema N+1: carrega os itens,
        // os materiais e o fornecedor de todas as compras em poucas
        // consultas, em vez de uma por cada compra.
        $query = Compra::with(['pessoa', 'itens.material']);

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        $compras = $query->orderByDesc('data')->orderByDesc('id')->paginate(20);

        return $this->ok([
            'itens' => CompraResource::collection($compras),
            'paginacao' => [
                'total' => $compras->total(),
                'pagina' => $compras->currentPage(),
                'por_pagina' => $compras->perPage(),
                'ultima_pagina' => $compras->lastPage(),
            ],
        ]);
    }

    public function show(Compra $compra): JsonResponse
    {
        $compra->load(['pessoa', 'itens.material']);

        return $this->ok(new CompraResource($compra));
    }

    public function store(CompraRequest $request, CompraService $servico): JsonResponse
    {
        $dados = $request->validated();

        $compra = $servico->registar(
            dados: [
                'pessoa_id' => $dados['pessoa_id'],
                'data' => $dados['data'] ?? null,
                'observacoes' => $dados['observacoes'] ?? null,
            ],
            itens: $dados['itens'],
            userId: $request->user()->id,
        );

        return $this->criado(
            new CompraResource($compra->load(['pessoa', 'itens.material'])),
            'Compra registada com sucesso.'
        );
    }

    /**
     * Exporta as compras em PDF. Mesma construcao de query do index()
     * (mesmos filtros, mesma ordenacao) — so troca a paginacao por um
     * limite de seguranca de 1000 linhas e o JSON por um PDF.
     */
    public function exportar(Request $request): Response
    {
        $query = Compra::with(['pessoa', 'itens.material']);

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        $totalCompras = round((float) (clone $query)->sum('total'), 2);

        $compras = $query->orderByDesc('data')->orderByDesc('id')->limit(1000)->get();

        return Pdf::loadView('pdf.compras', [
            'compras' => $compras,
            'totalCompras' => $totalCompras,
            'periodoTexto' => $this->periodoTexto($request),
        ])->download('compras-' . now()->format('Y-m-d') . '.pdf');
    }
}