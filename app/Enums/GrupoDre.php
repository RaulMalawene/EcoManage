<?php

namespace App\Enums;

enum GrupoDre: string
{
    case Operacional = 'operacional';
    case ImpostosOutros = 'impostos_outros';
    case NaoOperacional = 'nao_operacional';

    public function rotulo(): string
    {
        return match ($this) {
            self::Operacional => 'Despesa operacional',
            self::ImpostosOutros => 'Impostos e outros custos',
            self::NaoOperacional => 'Não operacional (fora do DRE)',
        };
    }

    /** Se entra no calculo do lucro do periodo. */
    public function entraNoDre(): bool
    {
        return $this !== self::NaoOperacional;
    }
}