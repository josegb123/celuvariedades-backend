<?php

namespace App\Services;

use App\Models\MovimientoFinanciero;
use App\Models\TipoMovimientoFinanciero;
use Exception;
use Illuminate\Support\Facades\DB;

class MovimientoFinancieroService
{
    /**
     * Registra una entrada o salida de dinero en el libro de caja de forma atómica.
     *
     * @param float $monto Monto del movimiento.
     * @param string $tipoMovimientoNombre Nombre del tipo de movimiento (ej: 'Venta de Productos').
     * @param string $metodoPago Método de pago asociado (ej: 'efectivo', 'tarjeta').
     * @param int $userId ID del usuario que registra el movimiento.
     * @param string $descripcion Descripción del movimiento.
     * @param string $referenciaTabla Nombre de la tabla de origen (ej: 'ventas', 'cuentas_por_cobrar').
     * @param int|null $referenciaId ID del registro en la tabla de origen.
     * @param int|null $ventaId ID de la venta asociada (si aplica).
     * @param int|null $cajaDiariaId ID de la caja diaria activa (si el movimiento implica flujo de dinero).
     * @return MovimientoFinanciero
     * @throws Exception
     */
    public function registrarMovimiento(
        float $monto,
        string $tipoMovimientoNombre,
        string $metodoPago,
        int $userId,
        string $descripcion,
        string $referenciaTabla,
        ?int $referenciaId,
        ?int $ventaId = null,
        ?int $cajaDiariaId = null
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