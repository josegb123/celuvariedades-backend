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
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();

            // Claves Foráneas
            $table->foreignId('venta_id')->constrained('ventas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onUpdate('cascade')->onDelete('restrict');

            // Datos del Detalle
            $table->decimal('cantidad', 15, 2); // Acepta decimales para productos por peso
            $table->decimal('precio_unitario', 15, 2)->comment('Precio de venta unitario en el momento de la venta');
            $table->decimal('subtotal', 15, 2)->comment('Cantidad * Precio Unitario (Neto de descuento de línea)');

            // --- Campos Históricos y de Utilidad (CRÍTICOS) ---
            $table->string('nombre_producto', 255)->comment('Nombre del producto al momento de la venta');
            $table->string('codigo_barra', 50)->nullable()->comment('Código de barras al momento de la venta');
            $table->decimal('precio_costo', 15, 2)->comment('Costo del producto al momento de la venta');

            // --- Desglose de Impuestos/Descuentos ---
            $table->decimal('iva_porcentaje', 15, 2)->comment('Tasa de IVA aplicada (Ej: 19)');
            $table->decimal('iva_monto', 15, 2)->default(0.00);
            $table->decimal('descuento_monto', 15, 2)->default(0.00);

            $table->timestamps();
            $table->softDeletes();

            // Índice compuesto para consultas rápidas de ítems por venta.
            $table->index(['venta_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};