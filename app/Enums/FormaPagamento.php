<?php

namespace App\Enums;

enum FormaPagamento: string
{
    case Dinheiro = 'dinheiro';
    case Material = 'material';

    public function rotulo(): string
    {
        return match ($this) {
            self::Dinheiro => 'Dinheiro',
            self::Material => 'Entrega de material',
        };
    }
}