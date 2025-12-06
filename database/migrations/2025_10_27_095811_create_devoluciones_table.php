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
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();

            // Claves Foráneas Obligatorias
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');

            // Campos de Registro
            $table->string('id_unico_producto')->unique(); // Identificador único para la unidad física
            $table->integer('cantidad')->default(1); // Cantidad siempre será 1 por unidad única
            $table->string('motivo');
            $table->decimal('costo_unitario', 10, 2);
            $table->text('notas')->nullable();
            $table->string('estado_gestion')->default('Pendiente'); // Ej: 'Pendiente', 'Contactado', 'Finalizada'

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
