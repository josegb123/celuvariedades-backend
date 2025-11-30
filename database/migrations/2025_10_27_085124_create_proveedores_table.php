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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_comercial', 150)->unique()->comment('Nombre de la empresa o persona.');
            $table->string('nombre_contacto', 100)->nullable()->comment('Nombre de la persona clave dentro del proveedor.');

            // Información de Identificación (RUC, NIT, etc.)
            $table->string('identificacion', 20)->unique()->nullable()->comment('Número de RUC, NIT o identificación fiscal.');

            // Información de Contacto
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->unique()->nullable();

            // Dirección
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();

            // Notas y estado
            $table->text('notas')->nullable()->comment('Notas internas sobre el proveedor o acuerdos.');
            $table->boolean('activo')->default(true)->comment('Indica si el proveedor está activo para realizar compras.');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};