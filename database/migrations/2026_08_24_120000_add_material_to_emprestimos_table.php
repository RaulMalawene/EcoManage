<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            // Preenchidos apenas quando tipo = material_emprestado: qual
            // material saiu do stock e quantos kg, para se poder ver
            // exactamente o que foi emprestado (nao so o valor em MT).
            $table->foreignId('material_id')->nullable()->after('tipo')->constrained('materiais');
            $table->decimal('quantidade_kg', 12, 3)->nullable()->after('material_id');
        });
    }

    public function down(): void
    {
        Schema::table('emprestimos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_id');
            $table->dropColumn('quantidade_kg');
        });
    }
};
