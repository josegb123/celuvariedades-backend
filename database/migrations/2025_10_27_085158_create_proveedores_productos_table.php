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
        Schema::create('proveedor_producto', function (Blueprint $table) {
            // Claves foráneas sin autoincremento para la tabla pivote
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');

            // Datos específicos de la relación:
            $table->decimal('precio_costo', 10, 2)->nullable()->comment('Precio al que el proveedor vende este producto.');
            $table->string('referencia_proveedor', 50)->nullable()->comment('Código o SKU que usa el proveedor para este producto.');

            // Definir la clave primaria compuesta para asegurar unicidad
            $table->primary(['proveedor_id', 'producto_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedor_producto');
    }
};