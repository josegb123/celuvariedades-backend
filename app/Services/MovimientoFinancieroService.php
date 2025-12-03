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
     * @param int|null $ventaId ID de la venta asociada (null para gastos varios).
     */
    public function registrarMovimiento(
        float $monto,
        string $tipoMovimientoNombre,
        string $metodoPago,
        int $userId,
        string $descripcion,
        string $referenciaTabla,
        int $referenciaId,
        ?int $ventaId = null,
        int $cajaDiariaId = 1
    ): MovimientoFinanciero {
        return DB::transaction(function () use ($monto, $tipoMovimientoNombre, $metodoPago, $userId, $descripcion, $referenciaTabla, $referenciaId, $ventaId, $cajaDiariaId) {

            $tipoMovimiento = TipoMovimientoFinanciero::where('nombre', $tipoMovimientoNombre)->first();

            if (!$tipoMovimiento) {
                throw new Exception("Tipo de movimiento financiero '{$tipoMovimientoNombre}' no encontrado.");
            }

            $movimiento = MovimientoFinanciero::create([
                'tipo_movimiento_id' => $tipoMovimiento->id,
                'descripcion' => $descripcion,
                'user_id' => $userId,
                'venta_id' => $ventaId,
                'monto' => $monto,
                'tipo' => $tipoMovimiento->tipo,
                'metodo_pago' => $metodoPago,
                'referencia_tabla' => $referenciaTabla,
                'referencia_id' => $referenciaId,
                'caja_diaria_id' => $cajaDiariaId,
            ]);

            return $movimiento;
        });
    }
}