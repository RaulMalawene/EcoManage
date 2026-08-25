<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\FormataMeses;
use App\Http\Concerns\FormataPeriodo;
use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\LancamentoCaixaResource;
use App\Models\LancamentoCaixa;
use App\Services\CaixaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consulta do livro-caixa (Modulo 7, RF-05 a RF-10).
 *
 * SO LEITURA. O caixa nao se escreve a mao: cada lancamento e' criado
 * automaticamente pelas compras, vendas e emprestimos. Aqui apenas se
 * consulta — o saldo, o extracto e o resumo de um periodo.
 */
class CaixaController extends Controller
{
    use RespostaApi, FormataMeses, FormataPeriodo;

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

    /**
     * Fluxo de caixa mes a mes, para o grafico de evolucao no frontend.
     * So chama CaixaService::resumoPeriodo() num loop, um mes de cada
     * vez — nenhum calculo novo aqui (mais antigo primeiro, incluindo
     * o mes corrente).
     */
    public function fluxoMensal(Request $request, CaixaService $caixa): JsonResponse
    {
        $meses = max(1, $request->integer('meses', 6));

        $resultado = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = now()->subMonths($i);

            $resumo = $caixa->resumoPeriodo($mes->copy()->startOfMonth(), $mes->copy()->endOfMonth());

            $resultado[] = [
                'mes' => $mes->format('Y-m'),
                'mes_rotulo' => $this->mesRotulo($mes),
                'entradas' => $resumo['entradas'],
                'saidas' => $resumo['saidas'],
                'saldo_periodo' => $resumo['saldo_periodo'],
            ];
        }

        return $this->ok($resultado);
    }

    /**
     * Exporta o extracto de caixa em PDF. Mesma construcao de query do
     * index() (mesmos filtros, mesma ordenacao cronologica) mais
     * saldoActual() — so troca a paginacao por um limite de seguranca
     * de 1000 linhas e o JSON por um PDF.
     */
    public function exportar(Request $request, CaixaService $caixa): Response
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

        $lancamentos = $query->limit(1000)->get();

        return Pdf::loadView('pdf.caixa', [
            'lancamentos' => $lancamentos,
            'saldoActual' => $caixa->saldoActual(),
            'periodoTexto' => $this->periodoTexto($request),
        ])->download('caixa-' . now()->format('Y-m-d') . '.pdf');
    }
}