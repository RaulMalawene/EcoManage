<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemVenda extends Model
{
    use HasFactory;

    protected $table = 'itens_venda';

    // Estes campos sao preenchidos pelo VendaService (nao vêm do cliente
    // da API — o controller nunca recebe o corpo inteiro deste model,
    // so material_id/quantidade/preco). Estao no fillable para o servico
    // os poder gravar via create().
    protected $fillable = [
        'venda_id',
        'material_id',
        'quantidade_kg',
        'preco_kg',
        'custo_kg',
        'subtotal',
        'custo_total',
        'lucro',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_kg' => 'decimal:3',
            'preco_kg' => 'decimal:2',
            'custo_kg' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'custo_total' => 'decimal:2',
            'lucro' => 'decimal:2',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function venda()
    {
        return $this->belongsTo(Venda::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}