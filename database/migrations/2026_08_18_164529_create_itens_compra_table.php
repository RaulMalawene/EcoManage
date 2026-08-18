<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiais');

            $table->decimal('quantidade_kg', 12, 3);
            $table->decimal('preco_kg', 12, 2);
            $table->decimal('subtotal', 14, 2);

            $table->timestamps();

            $table->index('material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_compra');
    }
};