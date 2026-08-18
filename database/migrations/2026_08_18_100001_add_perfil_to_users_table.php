<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Login por username: um operador de caixa pode nao ter email.
            $table->string('username')->unique()->after('name');
            $table->string('perfil')->default('operador')->after('password');
            $table->boolean('activo')->default(true)->after('perfil');

            $table->index('activo');
        });

        // O email deixa de ser obrigatorio (continua unico quando preenchido).
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['activo']);
            $table->dropColumn(['username', 'perfil', 'activo']);
        });
    }
};