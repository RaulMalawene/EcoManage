<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->foreignId('pessoa_id')->constrained('pessoas');
            $table->decimal('total', 14, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('data');
            $table->index(['pessoa_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};