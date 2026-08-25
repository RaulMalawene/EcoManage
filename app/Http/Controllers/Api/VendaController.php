<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\FormataPeriodo;
use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\VendaRequest;
use App\Http\Resources\VendaResource;
use App\Models\Venda;
use App\Services\VendaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registo e consulta de vendas (Modulo 5, RF-19 a RF-21).
 *
 * O store delega no VendaService, que dentro de uma transacao: grava a
 * venda, retira do stock (recusando se nao houver material), congela o
 * custo medio de cada item, calcula o lucro, e — se pago a pronto — da
 * entrada no caixa.
 *
 * Como nas compras: so index, show e store. Corrige-se por anulacao.
 */
class VendaController extends Controller
{
    use RespostaApi, FormataPeriodo;

    public function index(Request $request): JsonResponse
    {
        $query = Venda::with(['pessoa', 'itens.material']);

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        // ?por_receber=1 mostra so as vendas a credito ainda nao pagas
        if ($request->boolean('por_receber')) {
            $query->where('pago', false);
        }

        $vendas = $query->orderByDesc('data')->orderByDesc('id')->paginate(20);

        // Totais do periodo listado: util para o painel.
        $totalReceita = (clone $query)->sum('total');
        $totalLucro = (clone $query)->sum('lucro');

        return $this->ok([
            'itens' => VendaResource::collection($vendas),
            'resumo' => [
                'receita_total' => round((float) $totalReceita, 2),
                'lucro_total' => round((float) $totalLucro, 2),
            ],
            'paginacao' => [
                'total' => $vendas->total(),
                'pagina' => $vendas->currentPage(),
                'por_pagina' => $vendas->perPage(),
                'ultima_pagina' => $vendas->lastPage(),
            ],
        ]);
    }

    public function show(Venda $venda): JsonResponse
    {
        $venda->load(['pessoa', 'itens.material']);

        return $this->ok(new VendaResource($venda));
    }

    public function store(VendaRequest $request, VendaService $servico): JsonResponse
    {
        $dados = $request->validated();

        $venda = $servico->registar(
            dados: [
                'pessoa_id' => $dados['pessoa_id'],
                'data' => $dados['data'] ?? null,
                'pago' => $dados['pago'] ?? true,
                'observacoes' => $dados['observacoes'] ?? null,
            ],
            itens: $dados['itens'],
            userId: $request->user()->id,
        );

        return $this->criado(
            new VendaResource($venda->load(['pessoa', 'itens.material'])),
            'Venda registada com sucesso.'
        );
    }

    /**
     * Marca uma venda a credito como recebida: da entrada do dinheiro
     * no caixa na data do recebimento (RF-21).
     */
    public function receber(Venda $venda, Request $request, VendaService $servico): JsonResponse
    {
        $venda = $servico->registarRecebimento(
            venda: $venda,
            userId: $request->user()->id,
            data: $request->input('data'),
        );

        return $this->ok(
            new VendaResource($venda->load(['pessoa', 'itens.material'])),
            'Recebimento registado com sucesso.'
        );
    }

    /**
     * Exporta as vendas em PDF. Mesma construcao de query do index()
     * (mesmos filtros, mesma ordenacao) — so troca a paginacao por um
     * limite de seguranca de 1000 linhas e o JSON por um PDF.
     */
    public function exportar(Request $request): Response
    {
        $query = Venda::with(['pessoa', 'itens.material']);

        if ($request->filled('pessoa_id')) {
            $query->where('pessoa_id', $request->integer('pessoa_id'));
        }

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        if ($request->boolean('por_receber')) {
            $query->where('pago', false);
        }

        $totalReceita = round((float) (clone $query)->sum('total'), 2);
        $totalLucro = round((float) (clone $query)->sum('lucro'), 2);

        $vendas = $query->orderByDesc('data')->orderByDesc('id')->limit(1000)->get();

        return Pdf::loadView('pdf.vendas', [
            'vendas' => $vendas,
            'totalReceita' => $totalReceita,
            'totalLucro' => $totalLucro,
            'periodoTexto' => $this->periodoTexto($request),
        ])->download('vendas-' . now()->format('Y-m-d') . '.pdf');
    }
}