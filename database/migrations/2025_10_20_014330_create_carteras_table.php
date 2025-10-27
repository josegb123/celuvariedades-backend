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
        Schema::create('carteras', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('cliente_id');

            // Datos de la Deuda
            $table->decimal('monto_original', 10, 2);
            $table->decimal('monto_pendiente', 10, 2); // Campo que se reduce con cada pago
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 50)->default('Pendiente'); // Ej: Pendiente, Pagada, Vencida

            // Restricción: Una venta solo puede tener un registro de cartera.
            $table->unique('venta_id', 'uk_venta_cartera');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carteras');
    }
};
