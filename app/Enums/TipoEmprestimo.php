<?php

namespace App\Enums;

enum TipoEmprestimo: string
{
    case Dinheiro = 'dinheiro';
    case AdiantamentoMaterial = 'adiantamento_material';
    case MaterialEmprestado = 'material_emprestado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Dinheiro => 'Empréstimo em dinheiro',
            self::AdiantamentoMaterial => 'Adiantamento a abater em material',
            self::MaterialEmprestado => 'Empréstimo em material',
        };
    }

    /** Este tipo sai directamente do stock (kg de material), nao do caixa? */
    public function saiDoStock(): bool
    {
        return $this === self::MaterialEmprestado;
    }
}