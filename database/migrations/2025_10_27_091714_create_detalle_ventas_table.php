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
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();

            // Claves Foráneas
            $table->foreignId('venta_id')->constrained('ventas')->onUpdate('cascade')->onDelete('cascade'); // Si la Venta se borra, se borran sus detalles
            $table->foreignId('producto_id')->constrained('productos')->onUpdate('cascade')->onDelete('restrict');

            // Datos del Detalle
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2); // Cantidad * Precio Unitario

            $table->timestamps();
            $table->softDeletes();
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
