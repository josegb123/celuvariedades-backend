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
        Schema::create('detalle_devoluciones', function (Blueprint $table) {
            $table->id();

            // Claves Foráneas
            $table->foreignId('devolucion_id')->constrained('devoluciones')->onDelete('cascade'); // Si la Devolución se borra, se borran sus detalles
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');

            // Datos del Detalle
            $table->integer('cantidad');
            $table->decimal('precio_unitario_devolucion', 10, 2); // Precio al que se devolvió

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_devoluciones');
    }
};
