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

            // Relaciones
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('restrict'); // Devolución debe estar ligada a una Venta
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Usuario que procesó la devolución

            // Datos de la Devolución
            $table->decimal('monto_devuelto', 10, 2);
            $table->text('razon');
            $table->string('estado', 50)->default('Procesada'); // Ej: Procesada, Pendiente Reembolso

            $table->timestamps();
            $table->softDeletes();
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
