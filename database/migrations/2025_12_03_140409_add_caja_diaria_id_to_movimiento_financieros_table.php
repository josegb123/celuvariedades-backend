<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movimiento_financieros', function (Blueprint $table) {
            // PASO 1: Añadir la columna como NULLABLE temporalmente
            $table->unsignedBigInteger('caja_diaria_id')
                ->nullable()
                ->after('referencia_id')
                ->comment('ID de la caja diaria a la que pertenece el movimiento.');
        });

        // --- LÓGICA DE ACTUALIZACIÓN DE DATOS ---

        // PASO 2: Asignar un valor válido a los registros existentes
        // 1. Obtener el ID de la primera caja histórica (la más antigua).
        $primerCajaId = DB::table('cajas_diarias')->min('id');

        if ($primerCajaId) {
            // Actualizar todos los movimientos existentes que tengan caja_diaria_id = NULL
            DB::table('movimiento_financieros')
                ->whereNull('caja_diaria_id')
                ->update(['caja_diaria_id' => $primerCajaId]);
        }

        // --- APLICACIÓN DE RESTRICCIONES ---

        Schema::table('movimiento_financieros', function (Blueprint $table) {
            // PASO 3: Hacer la columna NOT NULL y añadir la clave foránea
            $table->unsignedBigInteger('caja_diaria_id')
                ->nullable(false) // CRÍTICO: Establecer como NO NULO
                ->change(); // Aplicar el cambio

            $table->foreign('caja_diaria_id')
                ->references('id')
                ->on('cajas_diarias')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimiento_financieros', function (Blueprint $table) {
            // 1. Eliminar la clave foránea
            $table->dropForeign(['caja_diaria_id']);

            // 2. Eliminar la columna
            $table->dropColumn('caja_diaria_id');
        });
    }
};