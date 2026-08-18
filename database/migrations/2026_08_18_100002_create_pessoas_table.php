<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo')->default('fornecedor'); // fornecedor|cliente|devedor|misto
            $table->string('telefone')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nome');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoas');
    }
};