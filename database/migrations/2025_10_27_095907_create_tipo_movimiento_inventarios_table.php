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
        Schema::create('tipo_movimiento_inventarios', function (Blueprint $table) {

            $table->id();
            $table->string('nombre', 50)->unique(); // Ej: Venta, Compra, Devolución, Ajuste, Plan Separe
            $table->string('tipo_operacion', 10); // 'ENTRADA' o 'SALIDA'
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_movimiento_inventarios');
    }
};
