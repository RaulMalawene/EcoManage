<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cria o primeiro utilizador administrador — o dono do ferro-velho.
 * Sem isto nao ha como fazer o primeiro login.
 *
 * IMPORTANTE: muda a palavra-passe no primeiro acesso. As credenciais
 * por defeito ('admin' / 'password') sao so para o arranque.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],           // nao duplica se ja existir
            [
                'name' => 'Administrador',
                'email' => null,
                'password' => 'password',       // o cast 'hashed' cifra automaticamente
                'perfil' => 'administrador',
                'activo' => true,
            ]
        );
    }
}