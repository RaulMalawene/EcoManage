<?php

namespace App\Enums;

enum CategoriaLancamento: string
{
    case SaldoInicial = 'saldo_inicial';
    case Compra = 'compra';
    case Venda = 'venda';
    case Despesa = 'despesa';
    case Emprestimo = 'emprestimo';
    case Recebimento = 'recebimento';
    case Adiantamento = 'adiantamento';
    case Retirada = 'retirada';

    public function rotulo(): string
    {
        return match ($this) {
            self::SaldoInicial => 'Saldo inicial',
            self::Compra => 'Compra de material',
            self::Venda => 'Venda',
            self::Despesa => 'Despesa',
            self::Emprestimo => 'Empréstimo concedido',
            self::Recebimento => 'Recebimento de dívida',
            self::Adiantamento => 'Adiantamento',
            self::Retirada => 'Retirada do dono',
        };
    }

    /**
     * Indica se este movimento de caixa e' tambem receita ou despesa
     * no DRE. Compras viram stock, emprestimos viram contas a receber,
     * recebimentos sao dinheiro que volta — nenhum deles afecta o lucro.
     *
     * O DRE nao se calcula a partir desta tabela; este metodo serve
     * para avisar o utilizador quando caixa e resultado divergem.
     */
    public function afectaResultado(): bool
    {
        return match ($this) {
            self::Venda, self::Despesa => true,
            default => false,
        };
    }
}