<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCompra extends Model
{
    use HasFactory;

    protected $table = 'itens_compra';

    protected $fillable = [
        'compra_id',
        'material_id',
        'quantidade_kg',
        'preco_kg',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_kg' => 'decimal:3',
            'preco_kg' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    // --- Calculo --------------------------------------------------------

    public function calcularSubtotal(): float
    {
        return round((float) $this->quantidade_kg * (float) $this->preco_kg, 2);
    }
}