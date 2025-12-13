<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('venta_id')
                ->unique() // Una venta solo debe tener una CuentaPorCobrar
                ->constrained('ventas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Información financiera
            $table->decimal('monto_original', 15, 2);
            $table->decimal('monto_pendiente', 15, 2); // Saldo actual

            // Gestión de Cartera
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 50)->default('Pendiente')->comment('Pendiente, Pagada, Vencida, Anulada');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_cobrar');
    }
};