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
        Schema::create('movimiento_financieros', function (Blueprint $table) {
            $table->id();
            $table->decimal('monto', 10, 2);
            $table->foreignId('tipo_movimiento_id')->constrained('tipo_movimiento_financieros');
            $table->string('descripcion');
            $table->date('fecha');
            $table->foreignId('venta_id')->nullable()->constrained('ventas');
            $table->foreignId('user_id')->constrained('users');
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
