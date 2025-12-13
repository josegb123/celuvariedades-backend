<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('abono_carteras', function (Blueprint $table) {
            $table->id();

            // Relación con la Cuenta por Cobrar
            $table->foreignId('cuenta_por_cobrar_id')
                ->constrained('cuentas_por_cobrar')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('restrict')
                ->comment('Usuario que registra el abono');

            $table->decimal('monto_abonado', 15, 2);
            $table->string('metodo_pago', 50); // Efectivo, Transferencia, etc.
            $table->string('referencia_pago')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abono_carteras');
    }
};