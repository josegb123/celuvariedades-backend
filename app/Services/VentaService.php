<?php

namespace App\Services;

use App\Models\CuentaPorCobrar;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\TipoVenta;
use App\Models\Venta;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class VentaService
{
    private AbonoService $abonoService;
    private InventarioService $inventarioService;
    private MovimientoFinancieroService $movimientoFinancieroService;
    private CajaDiariaService $cajaDiariaService;

    /**
     * @param InventarioService $inventarioService
     * @param MovimientoFinancieroService $movimientoFinancieroService
     * @param CajaDiariaService $cajaDiariaService
     */
    public function __construct(
        InventarioService $inventarioService,
        MovimientoFinancieroService $movimientoFinancieroService,
        CajaDiariaService $cajaDiariaService,
        AbonoService $abonoService
    ) {
        $this->inventarioService = $inventarioService;
        $this->movimientoFinancieroService = $movimientoFinancieroService;
        $this->cajaDiariaService = $cajaDiariaService;
        $this->abonoService = $abonoService;
    }

    /**
     * Procesa una venta completa, actualiza inventario, registra movimiento financiero y genera cartera si es necesario.
     *
     * @param  array  $validatedData  Datos validados (incluye user_id, iva_porcentaje, caja_diaria_id, abono_inicial).
     * @return Venta
     * @throws Exception
     */
    public function registrarVenta(array $validatedData): Venta
    {
        // 1. Iniciar la Transacción (Garantiza la atomicidad)
        return DB::transaction(function () use ($validatedData) {

            $tipoVenta = TipoVenta::findOrFail($validatedData['tipo_venta_id']);
            $items = $validatedData['items'];

            $ivaPorcentajeInput = (float) ($validatedData['iva_porcentaje'] ?? 0);
            $descuentoGlobalMonto = $validatedData['descuento_total'] ?? 0.00;

            // 2. Pre-cálculo y Preparación de Datos
            $calculos = $this->calcularTotales($items, $descuentoGlobalMonto, $ivaPorcentajeInput);

            $metodoPago = $validatedData['metodo_pago'] ?? ($tipoVenta->maneja_cartera ? 'credito' : 'efectivo');

            // --- Lógica de Control de Caja ---
            $cajaDiariaId = $validatedData['caja_diaria_id'] ?? null;

            if ($metodoPago === 'efectivo' || in_array($metodoPago, ['tarjeta', 'transferencia'])) {
                // Para cualquier pago de contado (incluyendo abono inicial de Plan Separe/Crédito)
                if (empty($cajaDiariaId)) {
                    // Solo se requiere la caja si hubo un movimiento de dinero (contado o abono)
                    if ($tipoVenta->maneja_cartera && ($validatedData['abono_inicial'] ?? 0.00) > 0) {
                        throw new Exception("Debe especificar el ID de la caja activa para registrar el abono inicial.");
                    } else if (!$tipoVenta->maneja_cartera) {
                        throw new Exception("Debe especificar el ID de la caja activa para registrar una venta en efectivo/tarjeta/transferencia.");
                    }
                }

                if (!empty($cajaDiariaId)) {
                    // Verificar que la caja exista y esté abierta para el usuario actual.
                    $caja = $this->cajaDiariaService->obtenerCajaActiva(Auth::id());

                    if (!$caja || $caja->id != $cajaDiariaId) {
                        throw new Exception("La caja con ID {$cajaDiariaId} no está activa o no pertenece al usuario.");
                    }
                }
            }


            // 3. Preparación de Datos de la Venta
            $datosVenta = [
                'user_id' => $validatedData['user_id'],
                'cliente_id' => $validatedData['cliente_id'] ?? null,
                'tipo_venta_id' => $tipoVenta->id,

                'caja_diaria_id' => $cajaDiariaId, // Asignar el ID de la caja si aplica.

                'subtotal' => $calculos['subtotal'],
                'descuento_total' => $calculos['descuento_total'],
                'iva_porcentaje' => $calculos['iva_porcentaje'],
                'iva_monto' => $calculos['iva_monto'],
                'total' => $calculos['total'],

                'estado' => $tipoVenta->maneja_cartera ? 'pendiente_pago' : ($validatedData['estado'] ?? 'finalizada'),
                'metodo_pago' => $metodoPago,
            ];

            // 4. Creación de la Cabecera de la Venta
            $venta = Venta::create($datosVenta);

            // 5. Procesamiento de Ítems (Detalles y Kárdex)
            foreach ($calculos['items'] as $itemCalculado) {


                // Estado financiero del producto
                if ($tipoVenta->maneja_cartera) {
                    $estadoItem = 'pendiente';
                } else {
                    $estadoItem = 'pagado';
                }



                // Creación del Detalle de Venta
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $itemCalculado['producto_id'],
                    'cantidad' => $itemCalculado['cantidad'],
                    'precio_unitario' => $itemCalculado['precio_unitario'],
                    'subtotal' => $itemCalculado['subtotal'],
                    'estado' => $estadoItem,
                    'nombre_producto' => $itemCalculado['nombre_producto'],
                    'codigo_barra' => $itemCalculado['codigo_barra'],
                    'precio_costo' => $itemCalculado['precio_costo'],
                    'iva_porcentaje' => $itemCalculado['iva_porcentaje'],
                    'iva_monto' => $itemCalculado['iva_monto'],
                    'descuento_monto' => $itemCalculado['descuento_monto'],
                ]);

                // Actualización del Inventario (Delegado)
                // Usar 'Plan Separe' o 'Crédito' (si aplica) para el tipo de movimiento de salida
                $tipoMovimientoNombre = $tipoVenta->nombre === 'Plan Separe' ? 'Transferencia Salida' : 'Venta';

                $this->inventarioService->ajustarStock(
                    productoId: $itemCalculado['producto_id'],
                    cantidad: $itemCalculado['cantidad'],
                    tipoMovimientoNombre: $tipoMovimientoNombre,
                    costoUnitario: $itemCalculado['precio_costo'],
                    userId: $venta->user_id,
                    referenciaTabla: 'ventas',
                    referenciaId: $venta->id
                );
            }
            // 6. Gestión de Movimiento Financiero (Solo si es venta de contado/tarjeta/transferencia)
            if (!$tipoVenta->maneja_cartera && $venta->estado === 'finalizada') {
                $resumenLimitado = Str::limit($venta->resumenProductos, 100, '...');
                // Registrar el ingreso total de la venta en el libro de caja
                $this->movimientoFinancieroService->registrarMovimiento(
                    monto: $venta->total,
                    tipoMovimientoNombre: 'Venta de Productos',
                    descripcion: $resumenLimitado,
                    metodoPago: $venta->metodo_pago,
                    ventaId: $venta->id,
                    userId: $venta->user_id,
                    referenciaTabla: 'ventas',
                    referenciaId: $venta->id,
                    cajaDiariaId: $cajaDiariaId
                );
            }

            // 7. Gestión de Cartera (Cuentas por Cobrar) - Lógica de abono y plazo integrada
            if ($tipoVenta->maneja_cartera) {
                if (!$venta->cliente_id) {
                    throw new Exception('Una venta a crédito/separe requiere un cliente.');
                }

                $abonoInicial = (float) ($validatedData['abono_inicial'] ?? 0.00);
                $montoTotalVenta = (float) $venta->total;

                // 1. Validación de abono inicial
                if ($abonoInicial > $montoTotalVenta) {
                    throw new Exception("El abono inicial ({$abonoInicial}) no puede ser mayor que el total de la venta ({$montoTotalVenta}).");
                }

                // 2. Determinar fecha de vencimiento y estado inicial
                $diasPlazo = $this->determinarPlazoCredito($calculos['items'], $tipoVenta->nombre);


                // Se crea la CuentaPorCobrar con el monto total
                //          y el monto pendiente igual al total.
                $montoPendienteInicial = $montoTotalVenta;
                $estadoCuentaInicial = 'Pendiente';

                // 3. Creación de la ÚNICA Cuenta por Cobrar
                $cuentaPorCobrar = CuentaPorCobrar::create([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'monto_original' => $montoTotalVenta,
                    'monto_pendiente' => $montoPendienteInicial,
                    'estado' => $estadoCuentaInicial,
                    'fecha_vencimiento' => now()->addDays($diasPlazo),
                ]);

                // 4. Procesar el Abono Inicial (Si existe)
                // Delegamos TODO el manejo del abono, movimiento financiero,
                // y actualización de saldo/estado a AbonoService.
                if ($abonoInicial > 0) {
                    $datosAbono = [
                        'cuenta_por_cobrar_id' => $cuentaPorCobrar->id,
                        'monto' => $abonoInicial,
                        'metodo_pago' => $validatedData['metodo_pago'] ?? 'efectivo', // Usar el método de pago de la venta
                        'user_id' => $venta->user_id,
                        'caja_diaria_id' => $cajaDiariaId, // La validación ya se hizo
                        'referencia_pago' => $validatedData['referencia_pago'] ?? "Abono inicial en venta {$venta->id}",
                        'tipo_abono' => "inicial",
                    ];

                    $this->abonoService->procesarAbono($datosAbono);

                    // Recargar la cuenta para reflejar la actualización de saldo/estado
                    $cuentaPorCobrar->refresh();
                }


                // 5. Actualizar el estado de la Venta (Si el AbonoService la dejó pagada)
                $nuevoEstadoVenta = $cuentaPorCobrar->estado === 'Pagada' ? 'finalizada' : 'pendiente_pago';

                if ($venta->estado !== $nuevoEstadoVenta) {
                    $venta->estado = $nuevoEstadoVenta;
                    $venta->save();
                }
            }

            return $venta->load('detalles.producto', 'cliente', 'user', 'cuentaPorCobrar', 'cuentaPorCobrar.abonos');
        });
    }

    /**
     * Determina el plazo máximo de crédito (en días) basado en el tipo de venta y productos vendidos.
     * @param array $itemsCalculados
     * @param string $tipoVentaNombre
     * @return int
     */
    private function determinarPlazoCredito(array $itemsCalculados, string $tipoVentaNombre): int
    {
        // Regla Plan Separe: 6 meses (180 días)
        if ($tipoVentaNombre === 'Plan Separe') {
            return 180;
        }

        $plazoDefault = 30; // Plazo predeterminado para Crédito
        $plazoCelulares = 180; // Plazo especial para Celulares a Crédito

        $productoIds = collect($itemsCalculados)->pluck('producto_id')->all();

        $productosConCategoria = Producto::whereIn('id', $productoIds)
            ->with('categoria')
            ->get();

        // Verificar si alguno de los productos pertenece a la categoría 'Celulares'
        $esCelular = $productosConCategoria->contains(function ($producto) {
            return optional($producto->categoria)->nombre === 'Celulares';
        });

        // Si es crédito normal y tiene celulares, se aplica el plazo largo
        return $esCelular ? $plazoCelulares : $plazoDefault;
    }

    /**
     * Consulta precios y calcula el subtotal, IVA y total de la venta.
     *
     * @param  array  $items  Array de productos y cantidades.
     * @param  float  $descuentoGlobalMonto  Descuento a aplicar al total.
     * @param  float  $ivaPorcentajeInput  Porcentaje de IVA a aplicar (e.g., 19.0). Si es 0, no se calcula.
     * @return array
     * @throws Exception
     */
    private function calcularTotales(array $items, float $descuentoGlobalMonto, float $ivaPorcentajeInput): array
    {
        $subtotalVenta = 0.00;
        $descuentoTotalAcumulado = 0.00;
        $ivaPorcentajeDB = $ivaPorcentajeInput / 100.0;
        $itemsCalculados = [];

        $productoIds = collect($items)->pluck('producto_id')->all();
        $productosDB = Producto::whereIn('id', $productoIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $producto = $productosDB->get($item['producto_id']);
            if (!$producto) {
                throw new Exception("Producto con ID {$item['producto_id']} no existe.");
            }

            $precioUnitario = $item['precio_unitario'] ?? $producto->precio_venta;
            $cantidad = $item['cantidad'];
            $descuentoLineaMonto = $item['descuento'] ?? 0.00;

            $subtotalBrutoItem = $precioUnitario * $cantidad;
            $subtotalNetoItem = $subtotalBrutoItem - $descuentoLineaMonto;

            // VALIDACIÓN: El descuento de línea no puede superar el subtotal bruto del ítem
            if ($descuentoLineaMonto > $subtotalBrutoItem) {
                throw new Exception("El descuento de línea ({$descuentoLineaMonto}) para el producto {$producto->nombre} no puede ser mayor que su subtotal bruto ({$subtotalBrutoItem}).");
            }

            $subtotalVenta += $subtotalNetoItem;
            $descuentoTotalAcumulado += $descuentoLineaMonto;

            $itemsCalculados[] = [
                'producto_id' => $item['producto_id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalNetoItem,
                'nombre_producto' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'precio_costo' => $producto->precio_compra,
                'descuento_monto' => $descuentoLineaMonto,
                'iva_porcentaje' => 0.0,
                'iva_monto' => 0.00,
            ];
        }

        // --- Cálculos a Nivel de Venta ---

        // VALIDACIÓN: El descuento global no puede superar el subtotal acumulado
        if ($descuentoGlobalMonto > $subtotalVenta) {
            throw new Exception("El descuento global ({$descuentoGlobalMonto}) no puede ser mayor que el subtotal neto de los ítems ({$subtotalVenta}).");
        }

        $subtotalNetoGlobal = $subtotalVenta - $descuentoGlobalMonto;
        $descuentoTotalAcumulado += $descuentoGlobalMonto;

        $ivaMontoFinal = 0.00;
        $ivaPorcentajeFinal = 0.0;

        // LÓGICA DE IVA CONDICIONAL: Solo calcular si el porcentaje es > 0
        if ($ivaPorcentajeInput > 0) {
            $ivaMontoFinal = $subtotalNetoGlobal * $ivaPorcentajeDB;
            $ivaPorcentajeFinal = $ivaPorcentajeInput;
        }

        $totalFinal = $subtotalNetoGlobal + $ivaMontoFinal;

        return [
            'subtotal' => $subtotalVenta,
            'descuento_total' => $descuentoTotalAcumulado,
            'iva_porcentaje' => $ivaPorcentajeFinal,
            'iva_monto' => $ivaMontoFinal,
            'total' => $totalFinal,
            'items' => $itemsCalculados,
        ];
    }
}
