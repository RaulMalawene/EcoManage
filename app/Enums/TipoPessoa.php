<?php

namespace App\Enums;

enum TipoPessoa: string
{
    case Fornecedor = 'fornecedor';
    case Cliente = 'cliente';
    case Devedor = 'devedor';
    case Misto = 'misto';

    public function rotulo(): string
    {
        return match ($this) {
            self::Fornecedor => 'Fornecedor / Biscateiro',
            self::Cliente => 'Cliente / Reciclador',
            self::Devedor => 'Devedor',
            self::Misto => 'Misto',
        };
    }
}