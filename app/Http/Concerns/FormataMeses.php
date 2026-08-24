<?php

namespace App\Http\Concerns;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Rotulo curto em portugues para um mes (ex.: "Ago/2026"), usado pelos
 * relatorios de evolucao mensal (DRE e fluxo de caixa). A app corre em
 * locale 'en' (config/app.php), por isso o translatedFormat() do Carbon
 * daria nomes em ingles — este mapa fixo evita depender do locale.
 */
trait FormataMeses
{
    private const MESES_PT = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
        5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
        9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
    ];

    protected function mesRotulo(CarbonInterface|Carbon $mes): string
    {
        return self::MESES_PT[$mes->month] . '/' . $mes->year;
    }
}
