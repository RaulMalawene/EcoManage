<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Relatorio de Despesas', 'periodoTexto' => $periodoTexto])

    @if ($despesas->isEmpty())
        <p class="vazio">Nenhuma despesa encontrada para os filtros indicados.</p>
    @else
        <table class="dados">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Categoria</th>
                    <th>Grupo DRE</th>
                    <th>Descricao</th>
                    <th>Pessoa</th>
                    <th class="num">Valor (MT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($despesas as $despesa)
                    <tr>
                        <td>{{ $despesa->data?->format('d/m/Y') }}</td>
                        <td>{{ $despesa->categoria }}</td>
                        <td>{{ $despesa->grupo_dre->rotulo() }}</td>
                        <td>{{ $despesa->descricao }}</td>
                        <td>{{ $despesa->pessoa?->nome ?? '—' }}</td>
                        <td class="num">{{ number_format((float) $despesa->valor, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumo">
            <tr class="total">
                <td class="label">Total de despesas</td>
                <td class="valor">{{ number_format($total, 2, ',', '.') }} MT</td>
            </tr>
        </table>
    @endif
</body>
</html>
