<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

/**
 * Texto do periodo (data_inicio/data_fim) para o cabecalho dos PDFs
 * exportados — os mesmos dois parametros que os index() ja aceitam.
 */
trait FormataPeriodo
{
    protected function periodoTexto(Request $request): ?string
    {
        if (! $request->filled('data_inicio') || ! $request->filled('data_fim')) {
            return 'Todos os registos';
        }

        return $request->date('data_inicio')->format('d/m/Y')
            . ' a ' . $request->date('data_fim')->format('d/m/Y');
    }
}
