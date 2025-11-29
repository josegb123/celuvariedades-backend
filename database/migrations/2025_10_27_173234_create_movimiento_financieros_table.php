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
        Schema::create('movimiento_financieros', function (Blueprint $table) {
            $table->id();

            // Campos de Monto y Referencia de Tipo
            $table->decimal('monto', 10, 2);
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimiento_financieros');
            $table->string('tipo', 10)->comment('Ingreso o Egreso');
            $table->string('descripcion');

            // Referencias de Auditoría
            $table->string('metodo_pago', 50);
            $table->foreignId('venta_id')->nullable()->constrained('ventas');
            $table->string('referencia_tabla', 50)->nullable()->comment('Tabla de origen, ej: abono_carteras');
            $table->unsignedBigInteger('referencia_id')->nullable()->comment('ID del registro en la tabla de origen');

            // Campos de Sistema
            $table->foreignId('user_id')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_financieros');
    }
};