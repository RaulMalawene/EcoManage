<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmprestimoRequest;
use App\Http\Requests\PagamentoRequest;
use App\Http\Resources\EmprestimoResource;
use App\Models\Emprestimo;
use App\Services\EmprestimoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Emprestimos e contas a receber — modulo prioritario (RF-22 a RF-30).
 *
 * conceder  -> cria a divida, tira o principal do caixa
 * pagar     -> reduz a divida (juro primeiro); dinheiro entra no caixa
 *              ou material entra no stock, conforme a forma
 */
class EmprestimoController extends Controller
{
    use RespostaApi;

    public function index(Request $request): JsonResponse
    {
        $query = Emprestimo::with(['pessoa', 'material']);

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        // ?estado=vencido | em_dia | liquidado
        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        // ?por_liquidar=1 mostra so os que ainda tem divida (RF-27)
        if ($request->boolean('por_liquidar')) {
            $query->where('estado', '!=', 'liquidado');
        }

        $emprestimos = $query->orderByDesc('data')->orderByDesc('id')->paginate(20);

        // Total ainda em divida (RF-29): soma dos saldos por liquidar.
        $totalEmDivida = Emprestimo::where('estado', '!=', 'liquidado')->sum('saldo_devedor');

        return $this->ok([
            'itens' => EmprestimoResource::collection($emprestimos),
            'resumo' => [
                'total_em_divida' => round((float) $totalEmDivida, 2),
            ],
            'paginacao' => [
                'total' => $emprestimos->total(),
                'pagina' => $emprestimos->currentPage(),
                'por_pagina' => $emprestimos->perPage(),
                'ultima_pagina' => $emprestimos->lastPage(),
            ],
        ]);
    }

    public function show(Emprestimo $emprestimo): JsonResponse
    {
        $emprestimo->load(['pessoa', 'material', 'pagamentos.material']);

        return $this->ok(new EmprestimoResource($emprestimo));
    }

    public function store(EmprestimoRequest $request, EmprestimoService $servico): JsonResponse
    {
        $emprestimo = $servico->registar($request->validated(), $request->user()->id);

        return $this->criado(
            new EmprestimoResource($emprestimo),
            'Emprestimo registado com sucesso.'
        );
    }

    /** Regista um pagamento a um emprestimo (RF-24). */
    public function pagar(PagamentoRequest $request, Emprestimo $emprestimo, EmprestimoService $servico): JsonResponse
    {
        $servico->registarPagamento($emprestimo, $request->validated(), $request->user()->id);

        return $this->criado(
            new EmprestimoResource($emprestimo->fresh()->load(['pessoa', 'material', 'pagamentos.material'])),
            'Pagamento registado com sucesso.'
        );
    }

    /**
     * Exporta os emprestimos em PDF. Mesma construcao de query do
     * index() (mesmos filtros, mesma ordenacao) — so troca a paginacao
     * por um limite de seguranca de 1000 linhas e o JSON por um PDF.
     */
    public function exportar(Request $request): Response
    {
        $query = Emprestimo::with(['pessoa', 'material']);

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        if ($request->boolean('por_liquidar')) {
            $query->where('estado', '!=', 'liquidado');
        }

        $emprestimos = $query->orderByDesc('data')->orderByDesc('id')->limit(1000)->get();

        $totalEmDivida = Emprestimo::where('estado', '!=', 'liquidado')->sum('saldo_devedor');

        return Pdf::loadView('pdf.emprestimos', [
            'emprestimos' => $emprestimos,
            'totalEmDivida' => round((float) $totalEmDivida, 2),
        ])->download('emprestimos-' . now()->format('Y-m-d') . '.pdf');
    }
}