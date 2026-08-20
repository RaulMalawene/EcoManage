<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\RespostaApi;
use App\Http\Controllers\Controller;
use App\Models\Emprestimo;
use App\Models\Material;
use App\Services\CaixaService;
use App\Services\DreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Relatorios: DRE (lucro real) e dashboard (visao geral do negocio).
 * Ambos so leem — nao gravam nada.
 */
class RelatorioController extends Controller
{
    use RespostaApi;

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
}