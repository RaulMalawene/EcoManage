<?php

namespace App\Models;

use App\Enums\TipoLancamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentoStock extends Model
{
    use HasFactory;

    protected $table = 'movimentos_stock';

    protected $fillable = [
        'data',
        'material_id',
        'tipo',
        'origem_tipo',
        'origem_id',
        'quantidade_kg',
        'custo_kg',
        'stock_apos_kg',
        'custo_medio_apos_kg',
        'observacoes',
        'user_id',
    ];

    // stock_apos_kg e custo_medio_apos_kg estao no fillable para o
    // StockService os gravar via create(). Nunca vêm do cliente da API —
    // o servico e' o unico que cria movimentos de stock.

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'tipo' => TipoLancamento::class,
            'quantidade_kg' => 'decimal:3',
            'custo_kg' => 'decimal:4',
            'stock_apos_kg' => 'decimal:3',
            'custo_medio_apos_kg' => 'decimal:4',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopeCronologico($query)
    {
        return $query->orderBy('data')->orderBy('id');
    }

    public function scopeDoMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }
}