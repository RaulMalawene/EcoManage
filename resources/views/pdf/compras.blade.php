<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Relatorio de Compras', 'periodoTexto' => $periodoTexto])

    @if ($compras->isEmpty())
        <p class="vazio">Nenhuma compra encontrada para os filtros indicados.</p>
    @else
        <table class="dados">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Fornecedor</th>
                    <th>Materiais</th>
                    <th class="num">Total (MT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($compras as $compra)
                    <tr>
                        <td>{{ $compra->data?->format('d/m/Y') }}</td>
                        <td>{{ $compra->pessoa?->nome ?? '—' }}</td>
                        <td>
                            @foreach ($compra->itens as $item)
                                {{ $item->material?->nome }} ({{ number_format((float) $item->quantidade_kg, 3, ',', '.') }} kg){{ ! $loop->last ? ', ' : '' }}
                            @endforeach
                        </td>
                        <td class="num">{{ number_format((float) $compra->total, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumo">
            <tr>
                <td class="label">Numero de compras</td>
                <td class="valor">{{ $compras->count() }}</td>
            </tr>
            <tr class="total">
                <td class="label">Total comprado</td>
                <td class="valor">{{ number_format($totalCompras, 2, ',', '.') }} MT</td>
            </tr>
        </table>
    @endif
</body>
</html>
