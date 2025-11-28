<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('saldos_clientes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cliente_id')->constrained('clientes');

            $table->foreignId('cuenta_por_cobrar_id')
                ->nullable()
                ->constrained('cuentas_por_cobrar'); // Puede ser null si el saldo viene de una devolución de contado

            $table->decimal('monto_original', 10, 2);
            $table->decimal('monto_pendiente', 10, 2);
            $table->string('estado', 50)->default('Activo');
            $table->string('motivo', 100);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldos_clientes');
    }
};