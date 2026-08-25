{{-- Estilos partilhados pelos PDFs de relatorio. O dompdf so suporta um
     subconjunto de CSS (sem flexbox/grid) — por isso tudo aqui e' tabelas
     e propriedades basicas. --}}
<style>
    @page { margin: 25px 30px; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        color: #222;
    }

    .cabecalho {
        border-bottom: 2px solid #2f6f3e;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }

    .cabecalho .empresa {
        font-size: 18px;
        font-weight: bold;
        color: #2f6f3e;
    }

    .cabecalho .relatorio {
        font-size: 13px;
        margin-top: 2px;
    }

    .cabecalho .meta {
        font-size: 10px;
        color: #666;
        margin-top: 2px;
    }

    table.dados {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    table.dados th {
        background-color: #2f6f3e;
        color: #fff;
        text-align: left;
        padding: 5px 6px;
        font-size: 10px;
    }

    table.dados td {
        padding: 4px 6px;
        border-bottom: 1px solid #ddd;
        font-size: 10px;
    }

    table.dados tr:nth-child(even) td {
        background-color: #f5f7f5;
    }

    .num { text-align: right; }

    .resumo {
        margin-top: 14px;
        width: 100%;
    }

    .resumo td {
        padding: 4px 6px;
        font-size: 11px;
    }

    .resumo .label { color: #555; }
    .resumo .valor { text-align: right; font-weight: bold; }
    .resumo .total td { border-top: 2px solid #2f6f3e; font-weight: bold; font-size: 12px; }

    .vazio {
        padding: 14px 0;
        color: #888;
        font-style: italic;
    }

    .negativo { color: #b3261e; }
</style>
