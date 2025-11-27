<?php

namespace Database\Seeders;

use App\Models\TipoMovimientoInventario;
use DateTime;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoMovimientoInventarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date = new DateTime('now');

        TipoMovimientoInventario::insert([
            // --- Flujo Transaccional Estándar ---
            ['nombre' => 'Venta', 'tipo_operacion' => 'SALIDA', 'created_at' => $date, 'updated_at' => $date],
            ['nombre' => 'Compra', 'tipo_operacion' => 'ENTRADA', 'created_at' => $date, 'updated_at' => $date],

            // --- Devoluciones ---
            ['nombre' => 'Devolución de Cliente', 'tipo_operacion' => 'ENTRADA', 'created_at' => $date, 'updated_at' => $date],
            ['nombre' => 'Devolución a Proveedor', 'tipo_operacion' => 'SALIDA', 'created_at' => $date, 'updated_at' => $date],

            // --- Ajustes Internos (CRÍTICO para Auditoría) ---
            ['nombre' => 'Ajuste por Pérdida (Mermas)', 'tipo_operacion' => 'SALIDA', 'created_at' => $date, 'updated_at' => $date],
            ['nombre' => 'Ajuste por Ganancia (Conteo)', 'tipo_operacion' => 'ENTRADA', 'created_at' => $date, 'updated_at' => $date],

            // --- Transferencias (Si tienes múltiples ubicaciones) ---
            // Si no tienes múltiples almacenes, puedes omitir estos dos.
            ['nombre' => 'Transferencia Salida', 'tipo_operacion' => 'SALIDA', 'created_at' => $date, 'updated_at' => $date],
            ['nombre' => 'Transferencia Entrada', 'tipo_operacion' => 'ENTRADA', 'created_at' => $date, 'updated_at' => $date],
        ]);
    }
}
