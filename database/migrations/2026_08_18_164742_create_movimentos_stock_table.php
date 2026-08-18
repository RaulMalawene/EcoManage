<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentos_stock', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->foreignId('material_id')->constrained('materiais');

            $table->string('tipo', 10);
            $table->string('origem_tipo', 30);
            $table->unsignedBigInteger('origem_id')->nullable();

            $table->decimal('quantidade_kg', 12, 3);
            $table->decimal('custo_kg', 12, 4);

            $table->decimal('stock_apos_kg', 12, 3);
            $table->decimal('custo_medio_apos_kg', 12, 4);

            $table->text('observacoes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index(['material_id', 'data']);
            $table->index(['origem_tipo', 'origem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentos_stock');
    }
};