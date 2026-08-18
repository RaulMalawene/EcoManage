<?php

namespace App\Models;

use App\Enums\TipoPessoa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pessoa extends Model
{
    use HasFactory, SoftDeletes;

    // O Laravel pluralizaria "Pessoa" para "pessoas" na mesma, mas
    // deixamos explicito para nao depender do inflector.
    protected $table = 'pessoas';

    protected $fillable = [
        'nome',
        'tipo',
        'telefone',
        'observacoes',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPessoa::class,
            'activo' => 'boolean',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function compras()
    {
        return $this->hasMany(Compra::class);
    }

    public function vendas()
    {
        return $this->hasMany(Venda::class);
    }

    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDoTipo($query, TipoPessoa $tipo)
    {
        return $query->whereIn('tipo', [$tipo->value, TipoPessoa::Misto->value]);
    }

    // --- Acessores ------------------------------------------------------

    /** Total ainda em divida por esta pessoa (RF-25). */
    public function getSaldoDevedorAttribute(): float
    {
        return (float) $this->emprestimos()
            ->where('estado', '!=', 'liquidado')
            ->sum('saldo_devedor');
    }
}