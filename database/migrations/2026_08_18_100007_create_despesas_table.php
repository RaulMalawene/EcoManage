<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();

            // data = quando saiu o dinheiro (livro-caixa)
            // data_competencia = a que mes pertence o gasto (DRE)
            // Sao iguais na maioria dos casos; o salario de Junho pago
            // a 5 de Julho e' o exemplo tipico em que divergem.
            $table->date('data');
            $table->date('data_competencia');

            $table->string('categoria', 50);   // transporte, combustivel, renda, energia...
            $table->string('grupo_dre', 30)->default('operacional');
            // operacional      -> entra nas despesas operacionais
            // impostos_outros  -> entra abaixo do resultado operacional
            // nao_operacional  -> nao entra no DRE (ex.: compra de equipamento)

            $table->string('descricao');
            $table->decimal('valor', 14, 2);

            $table->foreignId('pessoa_id')->nullable()->constrained('pessoas');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index('data_competencia');
            $table->index(['grupo_dre', 'categoria']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};