<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprestimo_id')->constrained('emprestimos');
            $table->date('data');
            $table->decimal('valor', 14, 2);

            // Reparticao do valor pago. So a parte de juro e' receita
            // no DRE; o principal e' dinheiro que voltou.
            $table->decimal('valor_juro', 14, 2)->default(0);
            $table->decimal('valor_principal', 14, 2)->default(0);

            $table->string('forma', 20)->default('dinheiro'); // dinheiro | material

            // Preenchidos apenas quando forma = material.
            $table->foreignId('material_id')->nullable()->constrained('materiais');
            $table->decimal('quantidade_kg', 12, 3)->nullable();

            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index(['emprestimo_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};