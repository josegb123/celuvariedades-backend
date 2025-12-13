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
        Schema::create('cajas_diarias', function (Blueprint $table) {
            $table->id();

            // Clave foránea al usuario que abrió la caja (el cajero)
            $table->foreignId('user_id')->constrained('users');

            // 1. Datos de Apertura
            $table->dateTime('fecha_apertura')->useCurrent();
            // Monto de efectivo con el que inicia la caja (base de cambio)
            $table->decimal('fondo_inicial', 15, 2);

            // 2. Datos de Cierre (serán NULL hasta que se cierre)
            $table->dateTime('fecha_cierre')->nullable();
            // Total de efectivo que el sistema espera ver
            $table->decimal('monto_cierre_teorico', 15, 2)->nullable();
            // Total de efectivo que el cajero cuenta físicamente
            $table->decimal('monto_cierre_fisico', 15, 2)->nullable();
            // Diferencia entre físico y teórico (sobrante o faltante)
            $table->decimal('diferencia', 15, 2)->nullable();

            // 3. Estado de la Caja
            $table->enum('estado', ['abierta', 'cerrada', 'cancelada'])->default('abierta');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas_diarias');
    }
};