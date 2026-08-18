<?php

namespace App\Enums;

enum TipoEmprestimo: string
{
    case Dinheiro = 'dinheiro';
    case AdiantamentoMaterial = 'adiantamento_material';

    public function rotulo(): string
    {
        return match ($this) {
            self::Dinheiro => 'Empréstimo em dinheiro',
            self::AdiantamentoMaterial => 'Adiantamento a abater em material',
        };
    }
}