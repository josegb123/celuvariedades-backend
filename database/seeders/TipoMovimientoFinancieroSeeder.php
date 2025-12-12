<?php

namespace Database\Seeders;

use App\Models\TipoMovimientoFinanciero;
use Illuminate\Database\Seeder;

class TipoMovimientoFinancieroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- 1. INGRESOS ---
        TipoMovimientoFinanciero::create([
            'nombre' => 'Venta de Productos',
            'descripcion' => 'Ingreso principal generado por ventas al contado o con tarjeta.',
            'tipo' => 'Ingreso', // Opcional, si tienes una columna 'tipo'
        ]);

        TipoMovimientoFinanciero::create([
            'nombre' => 'Abono a deuda',
            'descripcion' => 'Recibos de caja por pagos de clientes a ventas realizadas a crédito.',
            'tipo' => 'Ingreso',
        ]);

        TipoMovimientoFinanciero::create([
            'nombre' => 'Abono inicial a venta',
            'descripcion' => 'Abono inicial para una venta a crédito o plan separe.',
            'tipo' => 'Ingreso',
        ]);

        TipoMovimientoFinanciero::create([
            'nombre' => 'Ingreso Operacional Vario',
            'descripcion' => 'Ingresos por servicios o productos externos al oficio principal (el que definiste originalmente).',
            'tipo' => 'Ingreso',
        ]);

        // --- 2. EGRESOS ---
        TipoMovimientoFinanciero::create([
            'nombre' => 'Compra de Productos',
            'descripcion' => 'Salida de dinero para adquirir mercancía (Costo de Venta).',
            'tipo' => 'Egreso',
        ]);

        TipoMovimientoFinanciero::create([
            'nombre' => 'Gasto Operacional Vario',
            'descripcion' => 'Salida de dinero para pagos de nómina, servicios, o gastos generales.',
            'tipo' => 'Egreso',
        ]);

        TipoMovimientoFinanciero::create([
            'nombre' => 'Reembolso a Cliente',
            'descripcion' => 'Salida de dinero por la devolución de una venta previamente registrada.',
            'tipo' => 'Egreso',
        ]);
    }
}
