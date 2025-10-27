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
        Schema::create('movimiento_inventarios', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Usuario que generó el movimiento
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimiento_inventarios')->onDelete('restrict');

            // Datos del Movimiento
            $table->integer('cantidad'); // Siempre positiva. La acción (entrada/salida) está en tipo_movimiento
            $table->decimal('costo_unitario', 10, 2); // Costo al momento del movimiento

            // Referencia Transaccional
            $table->string('referencia_tabla', 50)->nullable(); // Ej: 'ventas', 'compras', 'devoluciones', 'ajustes'
            $table->unsignedBigInteger('referencia_id')->nullable(); // ID del registro en la tabla de referencia

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_inventarios');
    }
};
