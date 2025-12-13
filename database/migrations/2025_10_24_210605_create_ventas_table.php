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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            // --- Relaciones ---
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('restrict')
                ->comment('Vendedor que registró la venta');

            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->onUpdate('cascade')
                ->onDelete('restrict')
                ->comment('Cliente que recibe la factura/tiquete');

            // Usar foreignId para la tabla de catálogo
            $table->unsignedBigInteger('tipo_venta_id');

            // --- Totales Financieros ---
            $table->decimal('subtotal', 15, 2)->comment('Suma de subtotales de ítems antes de impuestos y descuento general');
            $table->decimal('descuento_total', 15, 2)->default(0.00)->comment('Descuento aplicado al total de la venta');

            // Mantener IVA en la cabecera (Aunque el detalle es el que contiene el desglose)
            $table->decimal('iva_porcentaje', 15, 2)->default(19.00)->comment('Tasa de IVA general de la venta');
            $table->decimal('iva_monto', 15, 2)->comment('Monto total de IVA');

            $table->decimal('total', 15, 2)->comment('Monto final a pagar');

            // --- Estado y Pago ---
            $table->string('estado', 50)->default('finalizada')->comment('finalizada, cancelada, pendiente_pago, reembolsada');
            $table->string('metodo_pago', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};