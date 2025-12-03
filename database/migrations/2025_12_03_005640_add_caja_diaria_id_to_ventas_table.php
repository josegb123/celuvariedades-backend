<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Añade la columna para vincular la venta a una sesión de caja
            $table->foreignId('caja_diaria_id')
                ->nullable() // Puede ser NULL para ventas antiguas o no POS
                ->constrained('cajas_diarias')
                ->after('user_id'); // Colócala después del user_id o donde prefieras
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Al revertir la migración, elimina la clave foránea y la columna
            $table->dropConstrainedForeignId('caja_diaria_id');
            $table->dropColumn('caja_diaria_id');
        });
    }
};