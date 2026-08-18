<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos_caixa', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('tipo', 10);          // entrada | saida
            $table->string('categoria', 30);     // ver App\Enums\CategoriaLancamento
            $table->string('descricao');
            $table->decimal('valor', 14, 2);

            // Saldo corrente apos este movimento (coluna TOTAL da planilha).
            // E' redundante de proposito: permite ler o saldo sem somar o
            // historico todo. Pode ser recalculado a qualquer momento.
            $table->decimal('saldo_apos', 14, 2)->default(0);

            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas');

            // Registo que originou o lancamento (compra, venda, despesa...).
            // Nao e' uma FK porque aponta para tabelas diferentes.
            $table->string('origem_tipo', 30)->nullable();
            $table->unsignedBigInteger('origem_id')->nullable();

            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();  // anular sem perder historico (RF-08)

            $table->index('data');
            $table->index(['tipo', 'categoria']);
            $table->index(['origem_tipo', 'origem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_caixa');
    }
};