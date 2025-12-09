<?php

namespace App\Services;

use App\Models\CajaDiaria;
use App\Models\CuentaPorCobrar;
use App\Models\Devolucion;
use App\Models\DetalleVenta;
use App\Models\SaldoCliente;
use App\Models\Venta;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DevolucionService
{
    private const ID_CLIENTE_GENERICO = 1;
    private InventarioService $inventarioService;
    private MovimientoFinancieroService $movimientoFinancieroService;

    public function __construct(InventarioService $inventarioService, MovimientoFinancieroService $movimientoFinancieroService)
    {
        $this->inventarioService = $inventarioService;
        $this->movimientoFinancieroService = $movimientoFinancieroService;
    }

    // ----------------------------------------------------------------------
    // --- ESCENARIO 1: ANULACIÓN TOTAL (Error de Registro)
    // ----------------------------------------------------------------------

    /**
     * Anula una venta completa (Soft Delete y reversión total de inventario, cartera y caja).
     * @param Venta $venta Venta a anular.
     * @param string $motivo Motivo de la anulación (e.g., "Error de registro").
     */
    public function anularVenta(Venta $venta, string $motivo = "Anulación completa de venta"): Venta
    {
        return DB::transaction(function () use ($venta, $motivo) {
            if ($venta->estado === 'cancelada') {
                throw new Exception("La venta ID {$venta->id} ya está cancelada.");
            }

            // 1. Reversión de Inventario (Devolución total al stock)
            foreach ($venta->detalles as $detalle) {
                $this->devolverStock($detalle, $detalle->cantidad);
            }

            // 2. Gestión de Cartera y Caja
            if ($venta->cuentaPorCobrar) {
                // Maneja la deuda y el posible reembolso de abonos
                $this->anularCuentaPorCobrar($venta->cuentaPorCobrar, $venta->cliente_id);
            } else {
                // Venta de Contado: Genera un egreso (reembolso) por el monto total.
                $montoReembolsar = $venta->monto_total;
                if ($montoReembolsar > 0) {
                    $this->registrarEgreso(
                        $montoReembolsar,
                        'Reembolso a Cliente',
                        'Efectivo', // Asumido
                        "Anulación total Venta #{$venta->id} (Contado)",
                        'ventas',
                        $venta->id
                    );
                }
            }

            // 3. Actualizar el estado de la Venta
            $venta->estado = 'cancelada';
            $venta->save();
            $venta->delete(); // Soft delete para auditoría

            return $venta;
        });
    }

    // ----------------------------------------------------------------------
    // --- ESCENARIO 2: DEVOLUCIÓN PARCIAL
    // ----------------------------------------------------------------------

    /**
     * Procesa la devolución parcial de productos de una venta.
     * @param Venta $venta Venta original.
     * @param array $itemsDevueltos Array de ['detalle_venta_id' => int, 'cantidad' => float, 'motivo' => string]
     * @param string $metodoReembolso Método de pago usado para el egreso (ej: 'Transferencia', 'Efectivo').
     * @return Venta Venta actualizada.
     */
    public function procesarDevolucionParcial(Venta $venta, array $itemsDevueltos, string $metodoReembolso = 'Efectivo'): Venta
    {
        return DB::transaction(function () use ($venta, $itemsDevueltos, $metodoReembolso) {

            // Determinar el cliente para el registro de auditoría (fallback al genérico)
            $clienteIdParaRegistro = $venta->cliente_id ?? self::ID_CLIENTE_GENERICO;

            if ($venta->estado === 'cancelada') {
                throw new Exception("No se puede devolver ítems de una venta cancelada.");
            }

            $montoTotalDevuelto = 0;

            foreach ($itemsDevueltos as $item) {
                $detalleId = $item['detalle_venta_id'];
                $cantidadDevuelta = (float) $item['cantidad'];
                $motivoDevolucion = $item['motivo'] ?? 'Devolución Parcial';


                $detalle = DetalleVenta::findOrFail($detalleId);

                // Validación de cantidad
                $cantidadPendiente = $detalle->cantidad - $detalle->cantidad_devuelta;
                if ($cantidadDevuelta <= 0 || $cantidadDevuelta > $cantidadPendiente) {
                    throw new Exception("Cantidad de devolución inválida para el detalle ID {$detalleId}. Pendiente: {$cantidadPendiente}");
                }

                // 1. Reversión de Inventario (Actualiza stock y Kárdex)
                $this->devolverStock($detalle, $cantidadDevuelta);

                // 2. REGISTRO DE AUDITORÍA DE DEVOLUCIÓN
                Devolucion::create([
                    'venta_id' => $venta->id,
                    'detalle_venta_id' => $detalleId,
                    'cliente_id' => $clienteIdParaRegistro,
                    'producto_id' => $detalle->producto_id,
                    'cantidad' => $cantidadDevuelta,
                    'motivo' => $motivoDevolucion,
                    'costo_unitario' => $detalle->precio_costo,
                    'estado_gestion' => 'Pendiente',
                ]);

                // 3. Actualizar el detalle de la venta (MODELO NETO para estadísticas)

                // Acumular la cantidad devuelta (Auditoría)
                $detalle->cantidad_devuelta += $cantidadDevuelta;

                // REDUCIR LA CANTIDAD VENDIDA RESTANTE (Neto)
                $detalle->cantidad -= $cantidadDevuelta;

                // Actualizar subtotal basado en la nueva cantidad neta
                $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;

                // Actualizar estado del detalle
                if ($detalle->cantidad <= 0.01) {
                    $detalle->estado = 'devuelta';
                    $detalle->cantidad = 0;
                } else {
                    $detalle->estado = 'parcialmente devuelta';
                }

                $detalle->save();

                // 4. Acumular el monto a reembolsar (usando precio de venta)
                $montoTotalDevuelto += $cantidadDevuelta * $detalle->precio_unitario;
            }


            // 5. Gestión Financiera: REGISTRO DEL EGRESO
            if ($montoTotalDevuelto > 0) {
                if ($venta->cuentaPorCobrar) {
                    // Venta a Crédito: Reduce la deuda o crea SaldoCliente (Nota Crédito)
                    $this->reducirCuentaPorCobrar($venta->cuentaPorCobrar, $montoTotalDevuelto);
                } else {
                    // Venta de Contado: Registra un EGRESO de caja (Reembolso en efectivo)
                    $this->registrarEgreso(
                        $montoTotalDevuelto,
                        'Reembolso a Cliente',
                        $metodoReembolso,
                        "Devolución de detalle #{$detalle->id} - Venta #{$venta->id} ", // Descripción genérica para parcial/total
                        'ventas',
                        $venta->id
                    );

                }
            }

            // 6. Actualizar estado y monto total de la venta
            $venta->load('detalles');

            // Recalcular el monto total sumando los subtotales netos restantes
            $nuevoMontoTotal = $venta->detalles->sum('subtotal');
            $venta->total = $nuevoMontoTotal;

            // Verificar si todos los ítems fueron devueltos (cantidad remanente = 0)
            $esDevolucionTotal = $venta->detalles->every(function (DetalleVenta $detalle) {
                return $detalle->cantidad <= 0.01;
            });

            if ($esDevolucionTotal) {
                $venta->estado = 'reembolsada';
            } else {
                $venta->estado = 'parcialmente devuelta';
            }

            $venta->save();

            return $venta;
        });
    }

    // ----------------------------------------------------------------------
    // --- MÉTODOS PROTEGIDOS AUXILIARES
    // ----------------------------------------------------------------------

    /**
     * Mueve stock de vuelta al inventario y registra el movimiento en el Kárdex.
     */
    protected function devolverStock(DetalleVenta $detalle, float $cantidad): void
    {
        $this->inventarioService->ajustarStock(
            productoId: $detalle->producto_id,
            cantidad: $cantidad,
            tipoMovimientoNombre: 'Devolución de Cliente',
            costoUnitario: $detalle->precio_costo,
            userId: auth()->id(),
            referenciaTabla: 'detalle_ventas',
            referenciaId: $detalle->id
        );
    }

    /**
     * Anula la cuenta por cobrar y gestiona el reembolso de abonos.
     */
    protected function anularCuentaPorCobrar(CuentaPorCobrar $cuentaPorCobrar, int $clienteId): CuentaPorCobrar
    {
        $montoAbonadoTotal = $cuentaPorCobrar->abonos->sum('monto_abonado');

        if ($montoAbonadoTotal > 0) {
            // 1. REEMBOLSO DE CAJA: Egreso directo de los abonos.
            $this->registrarEgreso(
                $montoAbonadoTotal,
                'Reembolso de Abonos',
                'Transferencia',
                "Anulación Venta #{$cuentaPorCobrar->venta_id}. Abonos: {$montoAbonadoTotal}",
                'cuentas_por_cobrar',
                $cuentaPorCobrar->id
            );
        }

        // 2. ANULAR CARTERA: Se limpia la deuda
        $cuentaPorCobrar->monto_pendiente = 0.00;
        $cuentaPorCobrar->estado = 'Anulada';
        $cuentaPorCobrar->save();
        $cuentaPorCobrar->abonos()->delete();

        return $cuentaPorCobrar;
    }

    /**
     * Reduce el monto pendiente de una Cuenta por Cobrar debido a una devolución.
     */
    protected function reducirCuentaPorCobrar(CuentaPorCobrar $cuentaPorCobrar, float $montoDevuelto): CuentaPorCobrar
    {
        if ($cuentaPorCobrar->estado !== 'pendiente') {
            throw new Exception("La cuenta por cobrar ID {$cuentaPorCobrar->id} no está activa.");
        }

        $cuentaPorCobrar->monto_pendiente -= $montoDevuelto;

        if ($cuentaPorCobrar->monto_pendiente <= 0.01) {
            $cuentaPorCobrar->monto_pendiente = 0.00;
            $cuentaPorCobrar->estado = 'pagada';
        }

        $cuentaPorCobrar->save();

        // Saldo a favor (monto pendiente negativo):
        if ($cuentaPorCobrar->monto_pendiente < 0) {
            $saldoAFavor = abs($cuentaPorCobrar->monto_pendiente);
            $clienteIdParaSaldo = $cuentaPorCobrar->venta->cliente_id ?? self::ID_CLIENTE_GENERICO;
            SaldoCliente::create([
                'cliente_id' => $clienteIdParaSaldo,
                'cuenta_por_cobrar_id' => $cuentaPorCobrar->id,
                'monto_original' => $saldoAFavor,
                'monto_pendiente' => $saldoAFavor,
                'estado' => 'Activo',
                'motivo' => "Excedente de Nota Crédito por Devolución Parcial Venta #{$cuentaPorCobrar->venta_id}",
            ]);

            $cuentaPorCobrar->monto_pendiente = 0.00;
            $cuentaPorCobrar->estado = 'pagada (Con Saldo a Favor)';
            $cuentaPorCobrar->save();
        }

        return $cuentaPorCobrar;
    }

    /**
     * Helper para registrar egresos financieros con la información de Caja Diaria.
     */
    protected function registrarEgreso(
        float $monto,
        string $tipoMovimientoNombre,
        string $metodoPago,
        string $descripcion,
        string $referenciaTabla,
        int $referenciaId
    ): void {
        // Buscar la caja diaria activa del usuario logueado
        $caja_diaria_id = CajaDiaria::query()
            ->where('user_id', auth()->id())
            ->whereNull('fecha_cierre')
            ->value('id');

        if (is_null($caja_diaria_id)) {
            // Deberías decidir si esto es un error fatal o solo una advertencia
            Log::warning('No se pudo asociar Movimiento Financiero a Caja Diaria activa para el usuario: ' . auth()->id());
        }

        $this->movimientoFinancieroService->registrarMovimiento(
            monto: $monto,
            tipoMovimientoNombre: $tipoMovimientoNombre,
            metodoPago: $metodoPago,
            userId: auth()->id(),
            descripcion: $descripcion,
            referenciaTabla: $referenciaTabla,
            referenciaId: $referenciaId,
            ventaId: $referenciaTabla === 'ventas' ? $referenciaId : null,
            cajaDiariaId: $caja_diaria_id
        );
    }
}