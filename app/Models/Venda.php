<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venda extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendas';

    protected $fillable = [
        'data',
        'pessoa_id',
        'total',
        'custo_total',
        'lucro',
        'pago',
        'data_recebimento',
        'observacoes',
        'user_id',
    ];

    // total, custo_total e lucro estao no fillable para o VendaService
    // os gravar, mas sao sempre calculados a partir dos itens — nunca
    // enviados pelo cliente da API.

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'data_recebimento' => 'date',
            'total' => 'decimal:2',
            'custo_total' => 'decimal:2',
            'lucro' => 'decimal:2',
            'pago' => 'boolean',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function itens()
    {
        return $this->hasMany(ItemVenda::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopeNoPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data', [$inicio, $fim]);
    }

    public function scopePorReceber($query)
    {
        return $query->where('pago', false);
    }

    // --- Acessores ------------------------------------------------------

    /** Margem bruta desta venda, em percentagem. */
    public function getMargemAttribute(): float
    {
        $total = (float) $this->total;

        return $total > 0 ? round((float) $this->lucro / $total * 100, 2) : 0.0;
    }
}