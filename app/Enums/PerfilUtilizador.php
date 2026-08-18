<?php

namespace App\Enums;

enum PerfilUtilizador: string
{
    case Administrador = 'administrador';
    case Operador = 'operador';
    case Consulta = 'consulta';

    public function rotulo(): string
    {
        return match ($this) {
            self::Administrador => 'Dono / Administrador',
            self::Operador => 'Operador de caixa',
            self::Consulta => 'Consulta',
        };
    }

    /** Pode criar, editar e anular registos. */
    public function podeEscrever(): bool
    {
        return $this !== self::Consulta;
    }

    /** Pode gerir utilizadores e configuracoes. */
    public function podeAdministrar(): bool
    {
        return $this === self::Administrador;
    }
}