<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pagamento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pagamentos';

    protected $fillable = [
        'emprestimo_id',
        'data',
        'valor',
        'valor_juro',
        'valor_principal',
        'forma',
        'material_id',
        'quantidade_kg',
        'user_id',
    ];

    // valor_juro e valor_principal estao no fillable para o servico os
    // gravar. O cliente da API so envia o valor total; a reparticao
    // e' feita pelo servico.

    // valor_juro e valor_principal sao repartidos pelo servico.
    // valor_juro e valor_principal sao repartidos pelo servico.

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'valor' => 'decimal:2',
            'valor_juro' => 'decimal:2',
            'valor_principal' => 'decimal:2',
            'quantidade_kg' => 'decimal:3',
            'forma' => FormaPagamento::class,
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function emprestimo()
    {
        return $this->belongsTo(Emprestimo::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Acessores ------------------------------------------------------

    public function ehEmMaterial(): bool
    {
        return $this->forma === FormaPagamento::Material;
    }

    /** Custo por kg do material recebido em pagamento. */
    public function getCustoKgAttribute(): ?float
    {
        if (! $this->ehEmMaterial() || ! $this->quantidade_kg) {
            return null;
        }

        return (float) $this->valor / (float) $this->quantidade_kg;
    }
}