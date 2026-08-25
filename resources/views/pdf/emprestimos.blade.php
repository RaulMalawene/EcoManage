<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Relatorio de Emprestimos', 'periodoTexto' => null])

    @if ($emprestimos->isEmpty())
        <p class="vazio">Nenhum emprestimo encontrado para os filtros indicados.</p>
    @else
        <table class="dados">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Pessoa</th>
                    <th>Tipo</th>
                    <th class="num">Valor total (MT)</th>
                    <th class="num">Saldo devedor (MT)</th>
                    <th>Estado</th>
                    <th>Vencimento</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($emprestimos as $emprestimo)
                    <tr>
                        <td>{{ $emprestimo->data?->format('d/m/Y') }}</td>
                        <td>{{ $emprestimo->pessoa?->nome ?? '—' }}</td>
                        <td>{{ $emprestimo->tipo->rotulo() }}</td>
                        <td class="num">{{ number_format((float) $emprestimo->valor_total, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $emprestimo->saldo_devedor, 2, ',', '.') }}</td>
                        <td>{{ $emprestimo->estado->rotulo() }}</td>
                        <td>{{ $emprestimo->data_vencimento?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumo">
            <tr>
                <td class="label">Numero de emprestimos</td>
                <td class="valor">{{ $emprestimos->count() }}</td>
            </tr>
            <tr class="total">
                <td class="label">Total em divida (todos os emprestimos por liquidar)</td>
                <td class="valor">{{ number_format($totalEmDivida, 2, ',', '.') }} MT</td>
            </tr>
        </table>
    @endif
</body>
</html>
