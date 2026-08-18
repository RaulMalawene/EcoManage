<?php

namespace App\Models;

use App\Enums\GrupoDre;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Despesa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'despesas';

    protected $fillable = [
        'data',
        'data_competencia',
        'categoria',
        'grupo_dre',
        'descricao',
        'valor',
        'pessoa_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'data_competencia' => 'date',
            'valor' => 'decimal:2',
            'grupo_dre' => Grupodre::class,
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

    /** Despesas de um periodo pela data de COMPETENCIA (para o DRE). */
    public function scopeCompetenciaNoPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data_competencia', [$inicio, $fim]);
    }

    /** Despesas de um periodo pela data de PAGAMENTO (para o caixa). */
    public function scopePagasNoPeriodo($query, $inicio, $fim)
    {
        return $query->whereBetween('data', [$inicio, $fim]);
    }

    public function scopeOperacionais($query)
    {
        return $query->where('grupo_dre', GrupoDre::Operacional);
    }
}