{{-- Cabecalho comum a todos os PDFs: nome da empresa, titulo do
     relatorio, periodo (quando aplicavel) e data de geracao. --}}
<div class="cabecalho">
    <div class="empresa">Jay Recicly</div>
    <div class="relatorio">{{ $titulo }}</div>
    @if (! empty($periodoTexto))
        <div class="meta">Periodo: {{ $periodoTexto }}</div>
    @endif
    <div class="meta">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
</div>
