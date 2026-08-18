<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiais', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();

            // Precos de referencia (o preco real fica gravado em cada item).
            $table->decimal('preco_compra_kg', 12, 2)->default(0);
            $table->decimal('preco_venda_kg', 12, 2)->default(0);

            // Quilos: 3 casas decimais porque a sucata pesa-se ao grama.
            $table->decimal('stock_kg', 12, 3)->default(0);
            $table->decimal('limite_alerta_kg', 12, 3)->nullable();

            // Custo medio ponderado do stock actual — base do calculo de lucro.
            $table->decimal('custo_medio_kg', 12, 4)->default(0);

            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiais');
    }
};