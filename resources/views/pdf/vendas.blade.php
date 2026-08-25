<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Relatorio de Vendas', 'periodoTexto' => $periodoTexto])

    @if ($vendas->isEmpty())
        <p class="vazio">Nenhuma venda encontrada para os filtros indicados.</p>
    @else
        <table class="dados">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Materiais</th>
                    <th class="num">Receita (MT)</th>
                    <th class="num">Custo (MT)</th>
                    <th class="num">Lucro (MT)</th>
                    <th>Pago?</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vendas as $venda)
                    <tr>
                        <td>{{ $venda->data?->format('d/m/Y') }}</td>
                        <td>{{ $venda->pessoa?->nome ?? '—' }}</td>
                        <td>
                            @foreach ($venda->itens as $item)
                                {{ $item->material?->nome }} ({{ number_format((float) $item->quantidade_kg, 3, ',', '.') }} kg){{ ! $loop->last ? ', ' : '' }}
                            @endforeach
                        </td>
                        <td class="num">{{ number_format((float) $venda->total, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $venda->custo_total, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $venda->lucro, 2, ',', '.') }}</td>
                        <td>{{ $venda->pago ? 'Sim' : 'Nao' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumo">
            <tr>
                <td class="label">Numero de vendas</td>
                <td class="valor">{{ $vendas->count() }}</td>
            </tr>
            <tr>
                <td class="label">Receita total</td>
                <td class="valor">{{ number_format($totalReceita, 2, ',', '.') }} MT</td>
            </tr>
            <tr class="total">
                <td class="label">Lucro total</td>
                <td class="valor">{{ number_format($totalLucro, 2, ',', '.') }} MT</td>
            </tr>
        </table>
    @endif
</body>
</html>
