<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pessoa_id')->constrained('pessoas');
            $table->date('data');

            $table->decimal('valor_principal', 14, 2);
            $table->decimal('juro_valor', 14, 2)->default(0);
            $table->decimal('valor_total', 14, 2);      // principal + juro
            $table->decimal('saldo_devedor', 14, 2);    // quanto falta receber

            $table->date('data_vencimento')->nullable();
            $table->text('motivo')->nullable();
            $table->string('tipo', 30)->default('dinheiro'); // dinheiro | adiantamento_material
            $table->string('estado', 20)->default('em_dia'); // em_dia | vencido | liquidado

            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index('data_vencimento');
            $table->index('estado');
            $table->index(['pessoa_id', 'estado']);   // lista de devedores (RF-27)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprestimos');
    }
};