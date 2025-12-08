<?php

namespace App\Services;

use App\Models\CuentaPorCobrar;
use App\Models\AbonoCartera;
use App\Models\DetalleVenta;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\Venta;

class CuentaPorCobrarService
{
  /**
   * Procesa el abono a una cuenta por cobrar, actualizando saldos y registrando el pago.
   * Este método NO registra el ingreso en el libro de caja.
   *
   * @param int $cuentaPorCobrarId ID de la deuda a abonar.
   * @param float $monto Monto que el cliente está pagando.
   * @param string $metodoPago Método de pago.
   * @param int $userId ID del usuario que registra el abono.
   * @return AbonoCartera
   * @throws Exception Si la cuenta no existe o el abono excede el saldo.
   */
  public function abonarCuentaPorCobrar(
    int $cuentaPorCobrarId,
    float $monto,
    string $metodoPago,
    int $userId,
    string $referenciaPago = ""
  ): AbonoCartera {

    return DB::transaction(function () use ($cuentaPorCobrarId, $monto, $metodoPago, $userId, $referenciaPago) {

      // 1. Bloqueo de registro para evitar doble pago simultáneo
      $cuenta = CuentaPorCobrar::lockForUpdate()->findOrFail($cuentaPorCobrarId);

      if ($cuenta->estado === 'Pagada' || $cuenta->estado === 'Anulada') {
        throw new Exception("La cuenta ID {$cuenta->id} ya está {$cuenta->estado}. No se puede abonar.");
      }

      if ($monto <= 0) {
        throw new Exception("El monto del abono debe ser positivo.");
      }
      // 2. Determinar el monto real a aplicar
      $montoRealAbonado = min($monto, $cuenta->monto_pendiente);
      $nuevoSaldo = $cuenta->monto_pendiente - $montoRealAbonado;

      // 3. Crear el registro AbonoCartera (El Recibo de Caja por Cartera)
      $abono = AbonoCartera::create([
        'cuenta_por_cobrar_id' => $cuenta->id,
        'user_id' => $userId,
        'monto_abonado' => $montoRealAbonado,
        'metodo_pago' => $metodoPago,
        'referencia_pago' => $referenciaPago,
      ]);

      // 4. Actualizar la CuentaPorCobrar
      $cuenta->monto_pendiente = $nuevoSaldo;

      if ($nuevoSaldo <= 0.00) {
        $cuenta->estado = 'Pagada';

        if ($cuenta->venta_id) {
          // Busca la venta por ID y actualiza su estado.          
          Venta::where('id', $cuenta->venta_id)->update(['estado' => 'finalizada', 'updated_at' => now()]);

          DetalleVenta::where('venta_id', $cuenta->venta_id)->update(['estado' => 'finalizada', 'updated_at' => now()]);
        }

      }

      $cuenta->save();

      return $abono;
    });
  }
}