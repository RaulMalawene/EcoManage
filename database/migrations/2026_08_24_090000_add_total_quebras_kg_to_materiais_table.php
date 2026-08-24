<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiais', function (Blueprint $table) {
            // Acumulado de kg perdidos por quebra (humidade, danos,
            // manuseamento, etc.) ao longo da vida do material — para o
            // dono ver de relance quanto se perde, sem ter de somar o
            // historico de movimentos_stock. So o StockService lhe mexe.
            $table->decimal('total_quebras_kg', 12, 3)->default(0)->after('stock_kg');
        });
    }

    public function down(): void
    {
        Schema::table('materiais', function (Blueprint $table) {
            $table->dropColumn('total_quebras_kg');
        });
    }
};
