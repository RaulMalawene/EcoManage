<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Relatorio de Materiais e Stock', 'periodoTexto' => null])

    @if ($materiais->isEmpty())
        <p class="vazio">Nenhum material encontrado para os filtros indicados.</p>
    @else
        <table class="dados">
            <thead>
                <tr>
                    <th>Material</th>
                    <th class="num">Stock (kg)</th>
                    <th class="num">Custo medio (MT/kg)</th>
                    <th class="num">Valor em stock (MT)</th>
                    <th class="num">Quebras acum. (kg)</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materiais as $material)
                    <tr>
                        <td>{{ $material->nome }}</td>
                        <td class="num">{{ number_format((float) $material->stock_kg, 3, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $material->custo_medio_kg, 4, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $material->stock_kg * (float) $material->custo_medio_kg, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $material->total_quebras_kg, 3, ',', '.') }}</td>
                        <td>{{ $material->activo ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumo">
            <tr>
                <td class="label">Total de materiais</td>
                <td class="valor">{{ $resumo['total_materiais'] }}</td>
            </tr>
            <tr class="total">
                <td class="label">Valor total imobilizado em stock</td>
                <td class="valor">{{ number_format($resumo['valor_stock_total'], 2, ',', '.') }} MT</td>
            </tr>
        </table>
    @endif
</body>
</html>
