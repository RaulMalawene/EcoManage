<?php

namespace App\Services;

use App\Enums\CategoriaLancamento;
use App\Enums\TipoLancamento;
use App\Models\LancamentoCaixa;
use Illuminate\Support\Facades\DB;

/**
 * Gere o livro-caixa. Cada entrada ou saida de dinheiro passa por aqui,
 * que grava o lancamento e calcula o saldo corrente (saldo_apos).
 *
 * Nota importante: o caixa mede DINHEIRO, nao lucro. Uma compra e' saida
 * de caixa mas nao e' custo (virou stock); um emprestimo concedido e'
 * saida mas nao e' despesa. O DRE trata do lucro noutro servico.
 */
class CaixaService
{
    /**
     * Regista um movimento e devolve o lancamento ja com saldo_apos.
     *
     * $origem liga o lancamento ao registo que o gerou (uma Compra, uma
     * Venda...), para se poder anular em cadeia e para auditoria.
     */
    public function registar(
        TipoLancamento $tipo,
        CategoriaLancamento $categoria,
        float $valor,
        string $descricao,
        int $userId,
        $data = null,
        ?int $pessoaId = null,
        ?string $origemTipo = null,
        ?int $origemId = null,
    ): LancamentoCaixa {
        return DB::transaction(function () use (
            $tipo, $categoria, $valor, $descricao, $userId, $data, $pessoaId, $origemTipo, $origemId
        ) {
            $saldoAnterior = $this->saldoActual();
            $saldoApos = round($saldoAnterior + ($valor * $tipo->sinal()), 2);

            return LancamentoCaixa::create([
                'data' => $data ?? now()->toDateString(),
                'tipo' => $tipo,
                'categoria' => $categoria,
                'descricao' => $descricao,
                'valor' => round($valor, 2),
                'saldo_apos' => $saldoApos,
                'pessoa_id' => $pessoaId,
                'origem_tipo' => $origemTipo,
                'origem_id' => $origemId,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Saldo de caixa neste momento.
     *
     * Soma directamente as entradas menos as saidas, em vez de ler o
     * saldo_apos do ultimo lancamento. E' mais robusto: o saldo_apos e'
     * um valor de leitura rapida que pode ficar desactualizado, mas a
     * soma dos movimentos e' sempre a verdade.
     */
    public function saldoActual(): float
    {
        $entradas = (float) LancamentoCaixa::entradas()->sum('valor');
        $saidas = (float) LancamentoCaixa::saidas()->sum('valor');

        return round($entradas - $saidas, 2);
    }

    /**
     * Recalcula saldo_apos de todos os lancamentos, por ordem cronologica.
     * Necessario depois de anular ou editar um movimento antigo (RF-08),
     * porque todos os saldos seguintes ficam desactualizados.
     */
    public function recalcularSaldos(): void
    {
        DB::transaction(function () {
            $saldo = 0.0;

            LancamentoCaixa::cronologico()
                ->lockForUpdate()
                ->each(function (LancamentoCaixa $lancamento) use (&$saldo) {
                    $saldo += (float) $lancamento->valor * $lancamento->tipo->sinal();
                    $lancamento->saldo_apos = round($saldo, 2);
                    $lancamento->save();
                });
        });
    }

    /**
     * Anula um lancamento (soft delete) e recalcula os saldos seguintes.
     * O historico fica: o registo nao desaparece, so e' marcado anulado.
     */
    public function anular(LancamentoCaixa $lancamento): void
    {
        DB::transaction(function () use ($lancamento) {
            $lancamento->delete();
            $this->recalcularSaldos();
        });
    }
}