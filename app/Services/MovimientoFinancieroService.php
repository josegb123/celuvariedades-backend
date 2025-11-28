<?php

namespace App\Services;

use App\Models\MovimientoFinanciero;
use App\Models\TipoMovimientoFinanciero;
use Exception;
use Illuminate\Support\Facades\DB;

class MovimientoFinancieroService
{
    /**
     * Registra una entrada o salida de dinero en el libro de caja.
     *
     * @param float $monto Monto del movimiento (siempre positivo, la dirección la da el tipoMovimiento).
     * @param string $tipoMovimientoNombre Nombre del tipo de movimiento (ej: 'Venta de Productos', 'Reembolso a Cliente').
     * @param string $metodoPago Método usado (efectivo, tarjeta, etc.).
     * @param int $userId Usuario que realiza/registra el movimiento.
     * @param string $referenciaTabla Nombre de la tabla de origen (ej: 'ventas', 'abono_carteras').
     * @param int $referenciaId ID del registro de origen.
     * @return MovimientoFinanciero
     * @throws Exception Si el tipo de movimiento no existe.
     */
    public function registrarMovimiento(
        float $monto,
        string $tipoMovimientoNombre,
        string $metodoPago,
        int $userId,
        string $referenciaTabla,
        int $referenciaId
    ): MovimientoFinanciero {
        return DB::transaction(function () use ($monto, $tipoMovimientoNombre, $metodoPago, $userId, $referenciaTabla, $referenciaId) {
            // 1. Obtener el tipo de movimiento y validar
            $tipoMovimiento = TipoMovimientoFinanciero::where('nombre', $tipoMovimientoNombre)->first();

            if (!$tipoMovimiento) {
                throw new Exception("Tipo de movimiento financiero '{$tipoMovimientoNombre}' no encontrado.");
            }

            // El monto del movimiento es siempre el valor absoluto de la transacción.
            // La columna 'tipo' del TipoMovimientoFinanciero ('Ingreso'/'Egreso') indica si es suma o resta en caja.

            // 2. Crear el registro
            $movimiento = MovimientoFinanciero::create([
                'tipo_movimiento_id' => $tipoMovimiento->id,
                'user_id' => $userId,
                'monto' => $monto,
                'tipo' => $tipoMovimiento->tipo, // 'Ingreso' o 'Egreso'
                'metodo_pago' => $metodoPago,
                'referencia_tabla' => $referenciaTabla,
                'referencia_id' => $referenciaId,
            ]);

            // 3. (OPCIONAL) Actualizar el saldo de la caja/banco si manejas un registro de saldos
            // Lógica de saldo de caja/banco iría aquí...

            return $movimiento;
        });
    }
}