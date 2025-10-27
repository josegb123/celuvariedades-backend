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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('clientes')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->unsignedBigInteger('tipo_venta_id');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento_total', 10, 2)->default(0.00);
            $table->decimal('iva_porcentaje', 5, 2)->default(19.00);
            $table->decimal('iva_monto', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('estado')->default('finalizada');
            $table->string('metodo_pago')->nullable();
            $table->date('fecha_emision')->default(today());

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
