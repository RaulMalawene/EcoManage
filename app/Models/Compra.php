<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'compras';

    protected $fillable = [
        'data',
        'pessoa_id',
        'total',
        'observacoes',
        'user_id',
    ];

    // O total esta no fillable para o CompraService o gravar, mas e'
    // sempre somado a partir dos itens — nunca enviado pelo cliente.

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'total' => 'decimal:2',
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function itens()
    {
        return $this->hasMany(ItemCompra::class);
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
}