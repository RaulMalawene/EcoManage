<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory, SoftDeletes;

    // OBRIGATORIO: o Laravel pluralizaria "Material" para "materials".
    protected $table = 'materiais';

    protected $fillable = [
        'nome',
        'preco_compra_kg',
        'preco_venda_kg',
        'stock_kg',
        'limite_alerta_kg',
        'activo',
    ];

    // custo_medio_kg fica de fora do fillable de proposito:
    // so o servico de stock o deve alterar.

    protected function casts(): array
    {
        return [
            'preco_compra_kg' => 'decimal:2',
            'preco_venda_kg' => 'decimal:2',
            'stock_kg' => 'decimal:3',
            'limite_alerta_kg' => 'decimal:3',
            'custo_medio_kg' => 'decimal:4',
            'activo' => 'boolean',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function itensCompra()
    {
        return $this->hasMany(ItemCompra::class);
    }

    public function itensVenda()
    {
        return $this->hasMany(ItemVenda::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /** Materiais que atingiram o limite para valer a pena vender (RF-15). */
    public function scopeEmAlerta($query)
    {
        return $query->whereNotNull('limite_alerta_kg')
            ->whereColumn('stock_kg', '>=', 'limite_alerta_kg');
    }

    // --- Acessores ------------------------------------------------------

    /** Capital investido neste material (RF-14). */
    public function getValorStockAttribute(): float
    {
        return (float) $this->stock_kg * (float) $this->custo_medio_kg;
    }
}