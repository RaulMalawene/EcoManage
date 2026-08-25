<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\FormataMeses;
use App\Http\Concerns\FormataPeriodo;
use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\Material;
use App\Services\CaixaService;
use App\Services\DreService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Relatorios: DRE (lucro real) e dashboard (visao geral do negocio).
 * Ambos so leem — nao gravam nada.
 */
class RelatorioController extends Controller
{
    use RespostaApi, FormataMeses, FormataPeriodo;

    /**
     * DRE de um periodo (Modulo 11). Se nao vierem datas, usa o mes
     * corrente. Diz se o negocio teve lucro ou prejuizo.
     */
    public function dre(Request $request, DreService $dre): JsonResponse
    {
        $inicio = $request->date('data_inicio') ?? now()->startOfMonth();
        $fim = $request->date('data_fim') ?? now()->endOfMonth();

        return $this->ok($dre->calcular($inicio, $fim));
    }

    /**
     * Painel geral: os numeros que o dono quer ver de relance —
     * saldo de caixa, valor do stock, dividas por receber, e o
     * lucro do mes corrente.
     */
    public function dashboard(Request $request, CaixaService $caixa, DreService $dre): JsonResponse
    {
        // Valor total imobilizado em stock (RF-14)
        $valorStock = Material::where('activo', true)->get()
            ->sum(fn ($m) => (float) $m->stock_kg * (float) $m->custo_medio_kg);

        // Total ainda em divida (RF-29)
        $totalEmDivida = (float) Emprestimo::where('estado', '!=', 'liquidado')
            ->sum('saldo_devedor');

        // Materiais em alerta de venda (RF-15)
        $materiaisEmAlerta = Material::where('activo', true)
            ->whereNotNull('limite_alerta_kg')
            ->whereColumn('stock_kg', '>=', 'limite_alerta_kg')
            ->count();

        // DRE do mes corrente para o lucro
        $dreMes = $dre->calcular(now()->startOfMonth(), now()->endOfMonth());

        return $this->ok([
            'saldo_caixa' => $caixa->saldoActual(),
            'valor_stock' => round($valorStock, 2),
            'total_em_divida' => round($totalEmDivida, 2),
            'materiais_em_alerta' => $materiaisEmAlerta,
            'mes_corrente' => [
                'receita' => $dreMes['receita_vendas'],
                'lucro_liquido' => $dreMes['lucro_liquido'],
                'margem_liquida_pct' => $dreMes['margem_liquida_pct'],
            ],
        ]);
    }

    /**
     * DRE mes a mes, para o grafico de evolucao no frontend. So chama
     * DreService::calcular() num loop, um mes de cada vez — nenhum
     * calculo novo aqui, so a soma dos meses corridos (mais antigo
     * primeiro, incluindo o mes corrente).
     */
    public function dreMensal(Request $request, DreService $dre): JsonResponse
    {
        $meses = max(1, $request->integer('meses', 6));

        $resultado = [];

        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = now()->subMonths($i);

            $linha = $dre->calcular($mes->copy()->startOfMonth(), $mes->copy()->endOfMonth());
            $linha['mes'] = $mes->format('Y-m');
            $linha['mes_rotulo'] = $this->mesRotulo($mes);

            $resultado[] = $linha;
        }

        return $this->ok($resultado);
    }

    /**
     * Exporta o DRE de um periodo em PDF: a mesma cascata do grafico do
     * frontend (Receita -> Custo -> Lucro bruto -> Juros -> Despesas ->
     * Resultado operacional -> Impostos -> Lucro liquido), directamente
     * do array que DreService::calcular() ja devolve — nenhum calculo
     * novo. Mesmo comportamento por omissao que dre(): sem datas, usa o
     * mes corrente.
     */
    public function exportarDre(Request $request, DreService $dre): Response
    {
        $inicio = $request->date('data_inicio') ?? now()->startOfMonth();
        $fim = $request->date('data_fim') ?? now()->endOfMonth();

        $resultado = $dre->calcular($inicio, $fim);

        return Pdf::loadView('pdf.dre', [
            'dre' => $resultado,
            'periodoTexto' => $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y'),
        ])->download('dre-' . now()->format('Y-m-d') . '.pdf');
    }
}