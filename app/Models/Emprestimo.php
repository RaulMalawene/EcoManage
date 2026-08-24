<?php

namespace App\Models;

use App\Enums\EstadoEmprestimo;
use App\Enums\TipoEmprestimo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Emprestimo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'emprestimos';

    protected $fillable = [
        'pessoa_id',
        'data',
        'valor_principal',
        'juro_valor',
        'valor_total',
        'saldo_devedor',
        'data_vencimento',
        'motivo',
        'tipo',
        'material_id',
        'quantidade_kg',
        'estado',
        'user_id',
    ];

    // valor_total, saldo_devedor e estado estao no fillable para o
    // EmprestimoService os gravar. Nunca vêm do cliente da API.

    // valor_total, saldo_devedor e estado sao calculados pelo servico.

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'data_vencimento' => 'date',
            'valor_principal' => 'decimal:2',
            'juro_valor' => 'decimal:2',
            'valor_total' => 'decimal:2',
            'saldo_devedor' => 'decimal:2',
            'tipo' => TipoEmprestimo::class,
            'quantidade_kg' => 'decimal:3',
            'estado' => EstadoEmprestimo::class,
        ];
    }

    // --- Relacoes -------------------------------------------------------

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    /** Material emprestado, quando tipo = material_emprestado. */
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopePorLiquidar($query)
    {
        return $query->where('estado', '!=', EstadoEmprestimo::Liquidado);
    }

    public function scopeVencidos($query)
    {
        return $query->where('estado', EstadoEmprestimo::Vencido);
    }

    /** Dividas a vencer nos proximos N dias (RF-28). */
    public function scopeAVencer($query, int $dias = 7)
    {
        return $query->porLiquidar()
            ->whereNotNull('data_vencimento')
            ->whereBetween('data_vencimento', [now()->toDateString(), now()->addDays($dias)->toDateString()]);
    }

    // --- Regras ---------------------------------------------------------

    /** Estado correcto face ao saldo e a data (regra do capitulo 5). */
    public function estadoCalculado(): EstadoEmprestimo
    {
        if ((float) $this->saldo_devedor <= 0.0) {
            return EstadoEmprestimo::Liquidado;
        }

        if ($this->data_vencimento && $this->data_vencimento->isPast()) {
            return EstadoEmprestimo::Vencido;
        }

        return EstadoEmprestimo::EmDia;
    }

    public function getTotalPagoAttribute(): float
    {
        return (float) $this->valor_total - (float) $this->saldo_devedor;
    }
}