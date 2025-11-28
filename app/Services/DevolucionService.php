<?php

namespace App\Services;

use App\Models\SaldoCliente;
use App\Models\Venta;
use App\Models\CuentaPorCobrar;
use Exception;
use Illuminate\Support\Facades\DB;

class DevolucionService
{
  private InventarioService $inventarioService;
  private MovimientoFinancieroService $movimientoFinancieroService; // Asumimos que tienes un servicio para Caja

  public function __construct(InventarioService $inventarioService, MovimientoFinancieroService $movimientoFinancieroService)
  {
    $this->inventarioService = $inventarioService;
    $this->movimientoFinancieroService = $movimientoFinancieroService;
  }

  /**
   * Anula una venta completa (Soft Delete y reversión total).
   * Ideal para anular ventas registradas por error inmediatamente.
   */
  public function anularVenta(Venta $venta): Venta
  {
    //  La anulación requiere una Transacción DB
    return DB::transaction(function () use ($venta) {

      if ($venta->estado === 'cancelada') {
        throw new Exception("La venta ID {$venta->id} ya está cancelada.");
      }

      // 1. Reversión de Inventario (Devolución total al stock)
      foreach ($venta->detalles as $detalle) {
        $this->inventarioService->ajustarStock(
          productoId: $detalle->producto_id,
          cantidad: $detalle->cantidad,
          tipoMovimientoNombre: 'Devolución de Cliente', // El tipo de movimiento de ENTRADA
          costoUnitario: $detalle->precio_costo,
          userId: auth()->id(), // Usuario que realiza la anulación
          referenciaTabla: 'ventas',
          referenciaId: $venta->id
        );
      }

      // 2. Gestión de Cartera
      if ($venta->cuentaPorCobrar) {
        $this->anularCuentaPorCobrar($venta->cuentaPorCobrar, $venta->cliente_id, auth()->id());
      }

      // 3. Gestión de Flujo de Caja (Si fue una venta de contado, asume un reembolso)
      // Lógica compleja: Si la venta ya fue pagada, genera un egreso (reembolso).
      // Si la venta es de contado, debes generar un movimiento de 'Egreso: Reembolso a Cliente'


      // 4. Actualizar el estado de la Venta
      $venta->estado = 'cancelada';
      $venta->save();
      $venta->delete(); // Soft delete para auditoría

      return $venta;
    });
  }

  /**
   * Anula la Cuenta por Cobrar asociada a la venta y gestiona saldos a favor.
   */
  protected function anularCuentaPorCobrar(CuentaPorCobrar $cuentaPorCobrar, int $clienteId, int $userId): CuentaPorCobrar
  {
    // El monto pendiente debe ser mayor a cero, de lo contrario, no hay deuda.
    if ($cuentaPorCobrar->monto_pendiente < $cuentaPorCobrar->monto_original) {

      // La deuda tenía abonos parciales. Calculamos el monto abonado total.
      $montoAbonadoTotal = $cuentaPorCobrar->abonos->sum('monto_abonado');

      //  El saldo a favor es la diferencia entre el monto abonado y el monto que DEBÍA pagar.
      // Como estamos anulando la VENTA COMPLETA, el cliente NO DEBÍA NADA.
      // Por lo tanto, el saldo a favor es el TOTAL de los abonos.

      $saldoAFavor = $montoAbonadoTotal;

      // 1. Crear el registro de Saldo a Favor (Nota Crédito)
      SaldoCliente::create([
        'cliente_id' => $clienteId,
        'cuenta_por_cobrar_id' => $cuentaPorCobrar->id,
        'monto_original' => $saldoAFavor,
        'monto_pendiente' => $saldoAFavor, // El saldo a favor completo está disponible
        'estado' => 'Activo',
        'motivo' => 'Anulación de Venta a Crédito con Abonos Previos',
      ]);

      // 2. Anular todos los abonos relacionados (opcional, si quieres dejar un rastro más limpio)
      $cuentaPorCobrar->abonos()->delete();

      // 3. Anular la Cuenta por Cobrar
      $cuentaPorCobrar->monto_pendiente = 0.00; // El saldo es cero, se movió a SaldoCliente
      $cuentaPorCobrar->estado = 'Anulada (Saldo Movido)';
      $cuentaPorCobrar->save();

    } else {
      // La deuda no tenía abonos, se anula limpiamente
      $cuentaPorCobrar->estado = 'Anulada';
      $cuentaPorCobrar->save();
    }

    return $cuentaPorCobrar;
  }

  // Aquí puedes agregar métodos más específicos:
  // public function procesarDevolucionParcial(Venta $venta, array $itemsDevueltos): Venta;
}