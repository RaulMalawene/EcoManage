<?php

namespace App\Models;

use App\Enums\CategoriaLancamento;
use App\Enums\TipoLancamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoCaixa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lancamentos_caixa';

    protected $fillable = [
        'data',
        'tipo',
        'categoria',
        'descricao',
        'valor',
        'saldo_apos',
        'pessoa_id',
        'origem_tipo',
        'origem_id',
        'user_id',
    ];

    // saldo_apos esta no fillable para o CaixaService o gravar via
    // create(). Nunca vem do cliente da API — e' sempre calculado.

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoLancamento::class,
            'categoria' => CategoriaLancamento::class,
            'valor' => 'decimal:2',
            'saldo_apos' => 'decimal:2',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopeEntradas($query)
    {
        return $query->where('tipo', TipoLancamento::Entrada);
    }

    public function scopeSaidas($query)
    {
        return $query->where('tipo', TipoLancamento::Saida);
    }

    public function scopeNoPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data', [$inicio, $fim]);
    }

    /** Ordem cronologica estavel — o id desempata movimentos do mesmo dia. */
    public function scopeCronologico($query)
    {
        return $query->orderBy('data')->orderBy('id');
    }

    // --- Acessores ------------------------------------------------------

    /** Valor com sinal: positivo nas entradas, negativo nas saidas. */
    public function getValorComSinalAttribute(): float
    {
        return (float) $this->valor * $this->tipo->sinal();
    }
}