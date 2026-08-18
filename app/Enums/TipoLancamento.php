<?php

namespace App\Enums;

enum TipoLancamento: string
{
    case Entrada = 'entrada';
    case Saida = 'saida';

    /** +1 para entradas, -1 para saidas — usado no calculo do saldo. */
    public function sinal(): int
    {
        return $this === self::Entrada ? 1 : -1;
    }

    public function rotulo(): string
    {
        return $this === self::Entrada ? 'ENTRADA' : 'SAÍDA';
    }
}