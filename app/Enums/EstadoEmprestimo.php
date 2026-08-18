<?php

namespace App\Enums;

enum EstadoEmprestimo: string
{
    case EmDia = 'em_dia';
    case Vencido = 'vencido';
    case Liquidado = 'liquidado';

    public function rotulo(): string
    {
        return match ($this) {
            self::EmDia => 'Em dia',
            self::Vencido => 'Vencido',
            self::Liquidado => 'Liquidado',
        };
    }

    public function cor(): string
    {
        return match ($this) {
            self::EmDia => 'verde',
            self::Vencido => 'vermelho',
            self::Liquidado => 'cinzento',
        };
    }
}