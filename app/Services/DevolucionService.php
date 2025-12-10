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
     *
     * @param Venta $venta Venta a anular.
     * @param string $motivo Motivo de la anulación (e.g., "Error de registro").
     * @param string $metodoReembolso Si es venta de contado: 'Efectivo', 'Transferencia', o 'SaldoCliente' (por defecto).
     */
    public function anularVenta(Venta $venta, string $motivo = "Anulación completa de venta", string $metodoReembolso = 'SaldoCliente'): Venta
    {
        return DB::transaction(function () use ($venta, $motivo, $metodoReembolso) {
            if ($venta->estado === 'cancelada') {
                throw new Exception("La venta ID {$venta->id} ya está cancelada.");
            }

            // 1. Reversión de Inventario (Devolución total al stock)
            foreach ($venta->detalles as $detalle) {
                $this->devolverStock($detalle, $detalle->cantidad);
            }

            // 2. Gestión de Cartera y Caja
            if ($venta->cuentaPorCobrar) {
                // Maneja la deuda y genera SaldoCliente por el total de abonos
                $this->anularCuentaPorCobrar($venta->cuentaPorCobrar, $venta->cliente_id);
            } else {
                // Venta de Contado: Se reembolsa o genera saldo por el monto total.
                $montoReembolsar = $venta->total;

                if ($montoReembolsar > 0) {
                    if ($metodoReembolso === 'Efectivo' || $metodoReembolso === 'Transferencia') {
                        // Reembolso en efectivo/transferencia solicitado
                        $this->registrarEgreso(
                            $montoReembolsar,
                            'Reembolso a Cliente',
                            $metodoReembolso,
                            "Anulación total Venta #{$venta->id} (Contado)",
                            'ventas',
                            $venta->id
                        );
                    } else {
                        // Por defecto: Genera Saldo Cliente (Nota Crédito)
                        $this->generarSaldoCliente(
                            monto: $montoReembolsar,
                            clienteId: $venta->cliente_id ?? self::ID_CLIENTE_GENERICO,
                            motivo: "Nota Crédito por Anulación Total Venta #{$venta->id} (Contado)",
                            ventaId: $venta->id
                        );
                    }
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
     *
     * @param Venta $venta Venta original.
     * @param array $itemsDevueltos Array de ['detalle_venta_id' => int, 'cantidad' => float, 'motivo' => string]
     * @param string $metodoReembolso Método de pago/gestión usado: 'Efectivo', 'Transferencia', o 'SaldoCliente' (por defecto).
     * @return Venta Venta actualizada.
     */
    public function procesarDevolucionParcial(Venta $venta, array $itemsDevueltos, string $metodoReembolso = 'SaldoCliente'): Venta
    {
        return DB::transaction(function () use ($venta, $itemsDevueltos, $metodoReembolso) {

            $clienteIdParaRegistro = $venta->cliente_id ?? self::ID_CLIENTE_GENERICO;

            if ($venta->estado === 'cancelada') {
                throw new Exception("No se puede devolver ítems de una venta cancelada.");
            }

            $montoTotalDevuelto = 0;

            foreach ($itemsDevueltos as $item) {
                // ... (Validación y creación de Devolucion, Reversión de Inventario, Actualización de DetalleVenta) ...

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
                $detalle->cantidad_devuelta += $cantidadDevuelta;
                $detalle->cantidad -= $cantidadDevuelta;
                $detalle->subtotal = $detalle->cantidad * $detalle->precio_unitario;

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


            // 5. Gestión Financiera: REGISTRO DEL EGRESO o SALDO CLIENTE
            if ($montoTotalDevuelto > 0) {
                if ($venta->cuentaPorCobrar) {
                    // Venta a Crédito: Reduce la deuda o genera SaldoCliente si hay excedente
                    $this->reducirCuentaPorCobrar($venta->cuentaPorCobrar, $montoTotalDevuelto);
                } elseif ($metodoReembolso === 'Efectivo' || $metodoReembolso === 'Transferencia') {
                    // Venta de Contado: Registra un EGRESO de caja (Reembolso en efectivo/transferencia)
                    $this->registrarEgreso(
                        $montoTotalDevuelto,
                        'Reembolso a Cliente',
                        $metodoReembolso,
                        "Devolución parcial Venta #{$venta->id} ",
                        'ventas',
                        $venta->id
                    );
                } else {
                    // Venta de Contado: Genera Saldo Cliente (Nota Crédito) por defecto
                    $this->generarSaldoCliente(
                        monto: $montoTotalDevuelto,
                        clienteId: $venta->cliente_id ?? self::ID_CLIENTE_GENERICO,
                        motivo: "Nota Crédito por Devolución Parcial Venta #{$venta->id}",
                        ventaId: $venta->id
                    );
                }
            }

            // 6. Actualizar estado y monto total de la venta            

            $venta->load('detalles');
            $nuevoMontoTotal = $venta->detalles->sum('subtotal');
            $venta->total = $nuevoMontoTotal;

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
     * Anula la cuenta por cobrar y gestiona el reembolso de abonos generando SaldoCliente.
     */
    protected function anularCuentaPorCobrar(CuentaPorCobrar $cuentaPorCobrar, int $clienteId): CuentaPorCobrar
    {
        $montoAbonadoTotal = $cuentaPorCobrar->abonos->sum('monto_abonado');

        if ($montoAbonadoTotal > 0) {
            // 1. CREAR SALDO A FAVOR (NOTA CRÉDITO) por el total abonado
            $clienteIdParaSaldo = $cuentaPorCobrar->venta->cliente_id ?? self::ID_CLIENTE_GENERICO;

            $this->generarSaldoCliente(
                monto: $montoAbonadoTotal,
                clienteId: $clienteIdParaSaldo,
                motivo: "Nota Crédito por Anulación Total Venta #{$cuentaPorCobrar->venta_id}. Monto Abonado: {$montoAbonadoTotal}",
                ventaId: $cuentaPorCobrar->venta_id
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
     * Reduce el monto pendiente de una Cuenta por Cobrar debido a una devolución y gestiona excedentes.
     */
    protected function reducirCuentaPorCobrar(CuentaPorCobrar $cuentaPorCobrar, float $montoDevuelto): CuentaPorCobrar
    {
        if ($cuentaPorCobrar->estado !== 'Pendiente') {
            throw new Exception("La cuenta por cobrar ID {$cuentaPorCobrar->id} no está activa. Estado: {$cuentaPorCobrar->estado}");
        }

        $cuentaPorCobrar->monto_pendiente -= $montoDevuelto;

        // 1. Si la cuenta queda saldada por la devolución (monto pendiente ~ 0)
        if ($cuentaPorCobrar->monto_pendiente <= 0.01) {
            $cuentaPorCobrar->monto_pendiente = 0.00;
            $cuentaPorCobrar->estado = 'Pagada';
        }

        // 2. LÓGICA DE SALDO A FAVOR (Nota Crédito) - si la devolución excede el saldo pendiente
        if ($cuentaPorCobrar->monto_pendiente < 0) {
            $saldoAFavor = abs($cuentaPorCobrar->monto_pendiente);

            $this->generarSaldoCliente(
                monto: $saldoAFavor,
                clienteId: $cuentaPorCobrar->venta->cliente_id ?? self::ID_CLIENTE_GENERICO,
                motivo: "Excedente de Nota Crédito por Devolución Parcial Venta #{$cuentaPorCobrar->venta_id}",
                ventaId: $cuentaPorCobrar->venta_id
            );

            // Resetear la cuenta por cobrar a cero
            $cuentaPorCobrar->monto_pendiente = 0.00;
            $cuentaPorCobrar->estado = 'Pagada (Con Saldo a Favor)';
        }

        $cuentaPorCobrar->save();
        return $cuentaPorCobrar;
    }

    /**
     * Genera un SaldoCliente (Nota Crédito) por el monto especificado.
     */
    protected function generarSaldoCliente(float $monto, int $clienteId, string $motivo, int $ventaId): void
    {
        SaldoCliente::create([
            'cliente_id' => $clienteId,
            'venta_id' => $ventaId,
            'monto_original' => $monto,
            'monto_pendiente' => $monto,
            'estado' => 'Activo',
            'motivo' => $motivo,
        ]);
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