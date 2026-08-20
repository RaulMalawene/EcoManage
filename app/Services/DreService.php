<?php

namespace App\Services;

use App\Enums\GrupoDre;
use App\Models\Despesa;
use App\Models\Pagamento;
use App\Models\Venda;

/**
 * Calcula a Demonstracao de Resultados (DRE) de um periodo — o relatorio
 * que diz se houve lucro ou prejuizo (Modulo 11, pedido do cliente).
 *
 * NAO guarda nada: le as vendas, os juros recebidos e as despesas do
 * periodo e monta a conta em camadas. E' calculado sempre a partir dos
 * lancamentos, nunca preenchido a mao.
 *
 * A conta segue exactamente o modelo do cliente:
 *
 *   Receita de vendas
 *   (-) Custo dos materiais vendidos (CMV)
 *   = Lucro bruto
 *   (+) Outras receitas (juros de emprestimos)
 *   (-) Despesas operacionais (renda, salarios, transporte, energia...)
 *   = Resultado operacional
 *   (-) Impostos e outros custos
 *   = Lucro liquido
 *
 * Distincao-chave face ao caixa: o DRE usa a RECEITA da venda (mesmo a
 * credito, mesmo que o dinheiro ainda nao tenha entrado) e a data de
 * COMPETENCIA das despesas. Mede lucro, nao dinheiro.
 */
class DreService
{
    public function calcular($inicio, $fim): array
    {
        // --- Receita e custo das vendas do periodo (pela data da venda) --
        $vendas = Venda::whereBetween('data', [$inicio, $fim])->get();

        $receitaVendas = round((float) $vendas->sum('total'), 2);
        $custoVendas = round((float) $vendas->sum('custo_total'), 2);
        $lucroBruto = round($receitaVendas - $custoVendas, 2);

        // --- Outras receitas: juros recebidos de emprestimos -------------
        // So a parte de juro dos pagamentos e' receita; o principal e'
        // dinheiro que voltou, nao ganho.
        $jurosRecebidos = round((float) Pagamento::whereBetween('data', [$inicio, $fim])
            ->sum('valor_juro'), 2);

        // --- Despesas do periodo (pela data de COMPETENCIA) --------------
        $despesas = Despesa::whereBetween('data_competencia', [$inicio, $fim])->get();

        // Operacionais: renda, salarios, transporte, energia...
        $despesasOperacionais = round((float) $despesas
            ->where('grupo_dre', GrupoDre::Operacional)
            ->sum('valor'), 2);

        // Impostos e outros custos: entram abaixo do resultado operacional.
        $impostosOutros = round((float) $despesas
            ->where('grupo_dre', GrupoDre::ImpostosOutros)
            ->sum('valor'), 2);

        // (As nao_operacionais ficam de fora do DRE de proposito.)

        // --- Camadas de resultado ----------------------------------------
        $resultadoOperacional = round(
            $lucroBruto + $jurosRecebidos - $despesasOperacionais, 2
        );

        $lucroLiquido = round($resultadoOperacional - $impostosOutros, 2);

        // Detalhe das despesas operacionais por categoria, para o relatorio.
        $despesasPorCategoria = $despesas
            ->where('grupo_dre', GrupoDre::Operacional)
            ->groupBy('categoria')
            ->map(fn ($grupo) => round((float) $grupo->sum('valor'), 2))
            ->toArray();

        return [
            'periodo' => [
                'inicio' => $inicio instanceof \DateTimeInterface ? $inicio->format('Y-m-d') : (string) $inicio,
                'fim' => $fim instanceof \DateTimeInterface ? $fim->format('Y-m-d') : (string) $fim,
            ],

            'receita_vendas' => $receitaVendas,
            'custo_materiais_vendidos' => $custoVendas,
            'lucro_bruto' => $lucroBruto,
            'margem_bruta_pct' => $receitaVendas > 0
                ? round($lucroBruto / $receitaVendas * 100, 1) : 0.0,

            'outras_receitas' => [
                'juros_emprestimos' => $jurosRecebidos,
            ],

            'despesas_operacionais' => $despesasOperacionais,
            'despesas_por_categoria' => $despesasPorCategoria,

            'resultado_operacional' => $resultadoOperacional,

            'impostos_outros' => $impostosOutros,

            'lucro_liquido' => $lucroLiquido,
            'margem_liquida_pct' => $receitaVendas > 0
                ? round($lucroLiquido / $receitaVendas * 100, 1) : 0.0,
        ];
    }
}