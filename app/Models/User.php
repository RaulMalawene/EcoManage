<?php

namespace App\Models;

use App\Enums\PerfilUtilizador;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'perfil',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'perfil' => PerfilUtilizador::class,
            'activo' => 'boolean',
        ];
    }

    // --- Permissoes (RF-02) ---------------------------------------------

    public function ehAdministrador(): bool
    {
        return $this->perfil->podeAdministrar();
    }

    public function podeEscrever(): bool
    {
        return $this->activo && $this->perfil->podeEscrever();
    }

    // --- Relacoes de auditoria (RF-04) ----------------------------------

    public function lancamentosCaixa()
    {
        return $this->hasMany(LancamentoCaixa::class);
    }

    public function emprestimos()
    {
        return $this->hasMany(Emprestimo::class);
    }

    // --- Scopes ---------------------------------------------------------

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}