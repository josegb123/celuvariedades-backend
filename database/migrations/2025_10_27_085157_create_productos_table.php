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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            // Claves Foráneas
            $table->foreignId('categoria_id')->constrained('categorias')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict'); // Usuario que lo registra

            // Datos Maestros
            $table->string('codigo_barra', 50)->unique()->nullable();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();

            // Precios e Inventario (CRÍTICO)
            $table->decimal('precio_compra', 10, 2);
            $table->decimal('precio_venta', 10, 2);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_reservado')->default(0);
            $table->integer('stock_minimo')->default(5);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias'); // Opcional, si quieres revertir en orden
    }
};
