<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tipo_ventas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique(); // Ej: Contado, Crédito, Plan Separe

            // Campos de Control para la Lógica del VentaService
            $table->boolean('maneja_cartera')->default(false)->comment('TRUE si genera una Cuenta por Cobrar');
            $table->boolean('reserva_stock')->default(false)->comment('TRUE si suma a stock_reservado en vez de restar stock_actual');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_ventas');
    }
};
