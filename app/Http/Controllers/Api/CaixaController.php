<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\LancamentoCaixaResource;
use App\Models\LancamentoCaixa;
use App\Services\CaixaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consulta do livro-caixa (Modulo 7, RF-05 a RF-10).
 *
 * SO LEITURA. O caixa nao se escreve a mao: cada lancamento e' criado
 * automaticamente pelas compras, vendas e emprestimos. Aqui apenas se
 * consulta — o saldo, o extracto e o resumo de um periodo.
 */
class CaixaController extends Controller
{
    use RespostaApi;

    /**
     * Extracto: lista de movimentos, cada um com o saldo corrente.
     * Filtros: intervalo de datas, tipo (entrada/saida), categoria.
     */
    public function index(Request $request, CaixaService $caixa): JsonResponse
    {
        $query = LancamentoCaixa::with('pessoa')->cronologico();

        if ($request->filled('data_inicio') && $request->filled('data_fim')) {
            $query->whereBetween('data', [
                $request->date('data_inicio'),
                $request->date('data_fim'),
            ]);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->string('categoria'));
        }

        $movimentos = $query->paginate(30);

        return $this->ok([
            'itens' => LancamentoCaixaResource::collection($movimentos),
            'saldo_actual' => $caixa->saldoActual(),
            'paginacao' => [
                'total' => $movimentos->total(),
                'pagina' => $movimentos->currentPage(),
                'por_pagina' => $movimentos->perPage(),
                'ultima_pagina' => $movimentos->lastPage(),
            ],
        ]);
    }

    /** Saldo actual do caixa — resposta rapida para o painel. */
    public function saldo(CaixaService $caixa): JsonResponse
    {
        return $this->ok(['saldo_actual' => $caixa->saldoActual()]);
    }

    /**
     * Relatorio de fluxo de caixa de um periodo (RF-09): quanto entrou,
     * quanto saiu, saldo do periodo. E' o primeiro relatorio pedido pelo
     * cliente. Se nao vierem datas, usa o mes corrente.
     */
    public function fluxo(Request $request, CaixaService $caixa): JsonResponse
    {
        $inicio = $request->date('data_inicio') ?? now()->startOfMonth();
        $fim = $request->date('data_fim') ?? now()->endOfMonth();

        $resumo = $caixa->resumoPeriodo($inicio, $fim);

        return $this->ok([
            'periodo' => [
                'inicio' => $inicio->toDateString(),
                'fim' => $fim->toDateString(),
            ],
            'entradas' => $resumo['entradas'],
            'saidas' => $resumo['saidas'],
            'saldo_periodo' => $resumo['saldo_periodo'],
            'saldo_actual' => $caixa->saldoActual(),
        ]);
    }
}