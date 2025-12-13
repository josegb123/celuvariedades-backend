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
        Schema::create('pedido_proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura_proveedor')->nullable()->unique();
            $table->date('fecha_entrega');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->decimal('monto_total', 15, 2);
            $table->string('estado')->default('recibido'); // 'recibido', 'pendiente', 'cancelado'
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_proveedores');
    }
};
