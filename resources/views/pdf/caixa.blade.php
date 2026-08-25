<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('pdf._estilos')
</head>
<body>
    @include('pdf._cabecalho', ['titulo' => 'Extracto de Caixa', 'periodoTexto' => $periodoTexto])

    @if ($lancamentos->isEmpty())
        <p class="vazio">Nenhum movimento encontrado para os filtros indicados.</p>
    @else
        <table class="dados">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th>Descricao</th>
                    <th>Pessoa</th>
                    <th class="num">Valor (MT)</th>
                    <th class="num">Saldo apos (MT)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lancamentos as $lancamento)
                    <tr>
                        <td>{{ $lancamento->data?->format('d/m/Y') }}</td>
                        <td>{{ $lancamento->tipo->rotulo() }}</td>
                        <td>{{ $lancamento->categoria->rotulo() }}</td>
                        <td>{{ $lancamento->descricao }}</td>
                        <td>{{ $lancamento->pessoa?->nome ?? '—' }}</td>
                        <td class="num {{ $lancamento->tipo->sinal() < 0 ? 'negativo' : '' }}">
                            {{ number_format((float) $lancamento->valor * $lancamento->tipo->sinal(), 2, ',', '.') }}
                        </td>
                        <td class="num">{{ number_format((float) $lancamento->saldo_apos, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="resumo">
            <tr>
                <td class="label">Numero de movimentos</td>
                <td class="valor">{{ $lancamentos->count() }}</td>
            </tr>
            <tr class="total">
                <td class="label">Saldo actual de caixa</td>
                <td class="valor">{{ number_format($saldoActual, 2, ',', '.') }} MT</td>
            </tr>
        </table>
    @endif
</body>
</html>
