<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendas', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->foreignId('pessoa_id')->constrained('pessoas');

            $table->decimal('total', 14, 2)->default(0);        // receita
            $table->decimal('custo_total', 14, 2)->default(0);  // CMV do DRE
            $table->decimal('lucro', 14, 2)->default(0);        // total - custo_total

            // Venda a credito: a receita conta no DRE na data da venda,
            // mas o dinheiro so entra no caixa quando for recebido.
            $table->boolean('pago')->default(true);
            $table->date('data_recebimento')->nullable();

            $table->text('observacoes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index(['pessoa_id', 'data']);
            $table->index('pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendas');
    }
};