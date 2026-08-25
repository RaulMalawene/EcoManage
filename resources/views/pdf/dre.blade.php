<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Demonstracao de Resultados (DRE)', 'periodoTexto' => $periodoTexto])

    {{-- Cascata: exactamente os campos ja devolvidos por DreService::calcular(),
         so formatados por linha (mesma ordem do grafico do frontend). --}}
    <table class="dados">
        <thead>
            <tr>
                <th>Linha</th>
                <th class="num">Valor (MT)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Receita de vendas</td>
                <td class="num">{{ number_format($dre['receita_vendas'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>(-) Custo dos materiais vendidos (CMV)</td>
                <td class="num negativo">-{{ number_format($dre['custo_materiais_vendidos'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>= Lucro bruto</strong> ({{ number_format($dre['margem_bruta_pct'], 1, ',', '.') }}%)</td>
                <td class="num"><strong>{{ number_format($dre['lucro_bruto'], 2, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>(+) Outras receitas — juros de emprestimos</td>
                <td class="num">+{{ number_format($dre['outras_receitas']['juros_emprestimos'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>(-) Despesas operacionais</td>
                <td class="num negativo">-{{ number_format($dre['despesas_operacionais'], 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>= Resultado operacional</strong></td>
                <td class="num"><strong>{{ number_format($dre['resultado_operacional'], 2, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>(-) Impostos e outros custos</td>
                <td class="num negativo">-{{ number_format($dre['impostos_outros'], 2, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td><strong>= Lucro liquido</strong> ({{ number_format($dre['margem_liquida_pct'], 1, ',', '.') }}%)</td>
                <td class="num"><strong>{{ number_format($dre['lucro_liquido'], 2, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if (! empty($dre['despesas_por_categoria']))
        <p style="margin-top:16px; font-weight:bold;">Despesas operacionais por categoria</p>
        <table class="dados">
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="num">Valor (MT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dre['despesas_por_categoria'] as $categoria => $valor)
                    <tr>
                        <td>{{ $categoria }}</td>
                        <td class="num">{{ number_format($valor, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
