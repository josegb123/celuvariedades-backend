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
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();

            // Claves Foráneas Obligatorias
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');

            // CLAVE CRÍTICA: Traza la devolución directamente a la línea original de la venta.
            // Esto reemplaza la necesidad de 'id_unico_producto' para las devoluciones parciales.
            $table->foreignId('detalle_venta_id')->constrained('detalle_ventas')->onDelete('cascade');

            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');

            // Campos de Registro
            // Ahora la cantidad puede ser mayor a 1, reflejando el número de unidades devueltas de esa línea.
            $table->decimal('cantidad', 8, 2);

            $table->string('motivo');

            // Se usa el costo unitario de la venta original para el cálculo del Kárdex/Inventario.
            $table->decimal('costo_unitario', 10, 2);

            $table->text('notas')->nullable();

            $table->string('estado_gestion')->default('Finalizada'); // Asumimos que la creación en el Service es el fin del proceso

            // Índices compuestos para búsquedas rápidas si es necesario (Opcional, pero recomendado)
            $table->index(['venta_id', 'detalle_venta_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devoluciones');
    }
};