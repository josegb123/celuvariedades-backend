<?php

namespace App\Services;

use App\Models\CuentaPorCobrar;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\TipoVenta;
use App\Models\Venta;
use Exception;
use Illuminate\Support\Facades\DB;

class VentaService
{
    private InventarioService $inventarioService;
    private MovimientoFinancieroService $movimientoFinancieroService;

    /**
     * @param InventarioService $inventarioService
     * @param MovimientoFinancieroService $movimientoFinancieroService
     */
    public function __construct(InventarioService $inventarioService, MovimientoFinancieroService $movimientoFinancieroService)
    {
        $this->inventarioService = $inventarioService;
        $this->movimientoFinancieroService = $movimientoFinancieroService;
    }

    /**
     * Procesa una venta completa, actualiza inventario, registra movimiento financiero y genera cartera si es necesario.
     *
     * @param  array  $validatedData  Datos validados (incluye user_id y iva_porcentaje).
     * @return Venta
     * @throws Exception
     */
    public function registrarVenta(array $validatedData): Venta
    {
        // 1. Iniciar la Transacción (Garantiza la atomicidad)
        return DB::transaction(function () use ($validatedData) {

            $tipoVenta = TipoVenta::findOrFail($validatedData['tipo_venta_id']);
            $items = $validatedData['items'];

            // Obtener el porcentaje de IVA desde los datos validados (será 0 si es null/desmarcado)
            $ivaPorcentajeInput = (float) ($validatedData['iva_porcentaje'] ?? 0);
            $descuentoGlobalMonto = $validatedData['descuento_total'] ?? 0.00;

            // 2. Pre-cálculo y Preparación de Datos
            $calculos = $this->calcularTotales($items, $descuentoGlobalMonto, $ivaPorcentajeInput);

            // El estado por defecto se establece a partir del tipo de venta
            $datosVenta = [
                'user_id' => $validatedData['user_id'],
                'cliente_id' => $validatedData['cliente_id'] ?? null,
                'tipo_venta_id' => $tipoVenta->id,

                // Totales calculados
                'subtotal' => $calculos['subtotal'],
                'descuento_total' => $calculos['descuento_total'],
                'iva_porcentaje' => $calculos['iva_porcentaje'],
                'iva_monto' => $calculos['iva_monto'],
                'total' => $calculos['total'],

                // Estado y método de pago
                'estado' => $tipoVenta->maneja_cartera ? 'pendiente_pago' : ($validatedData['estado'] ?? 'finalizada'),
                'metodo_pago' => $validatedData['metodo_pago'] ?? ($tipoVenta->maneja_cartera ? 'credito' : 'efectivo'),
            ];

            // 3. Creación de la Cabecera de la Venta
            $venta = Venta::create($datosVenta);

            // 4. Procesamiento de Ítems (Detalles y Kárdex)
            foreach ($calculos['items'] as $itemCalculado) {

                // Creación del Detalle de Venta
                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $itemCalculado['producto_id'],
                    'cantidad' => $itemCalculado['cantidad'],
                    'precio_unitario' => $itemCalculado['precio_unitario'],
                    'subtotal' => $itemCalculado['subtotal'],
                    'nombre_producto' => $itemCalculado['nombre_producto'],
                    'codigo_barra' => $itemCalculado['codigo_barra'],
                    'precio_costo' => $itemCalculado['precio_costo'],
                    'iva_porcentaje' => $itemCalculado['iva_porcentaje'],
                    'iva_monto' => $itemCalculado['iva_monto'],
                    'descuento_monto' => $itemCalculado['descuento_monto'],
                ]);

                // Actualización del Inventario (Delegado)
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

            // 5. Gestión de Movimiento Financiero (Solo si es venta de contado/tarjeta/transferencia)
            if (!$tipoVenta->maneja_cartera && $venta->estado === 'finalizada') {
                // Registrar el ingreso total de la venta en el libro de caja
                $this->movimientoFinancieroService->registrarMovimiento(
                    monto: $venta->total,
                    tipoMovimientoNombre: 'Venta de Productos',
                    descripcion: $venta->resumenProductos,
                    metodoPago: $venta->metodo_pago,
                    ventaId: $venta->id,
                    userId: $venta->user_id,
                    referenciaTabla: 'ventas',
                    referenciaId: $venta->id
                );
            }

            // Gestión de Cartera (Cuentas por Cobrar)
            if ($tipoVenta->maneja_cartera) {
                if (!$venta->cliente_id) {
                    throw new Exception('Una venta a crédito/separe requiere un cliente.');
                }

                // Determinar la fecha de vencimiento
                $diasPlazo = $this->determinarPlazoCredito($calculos['items']);

                CuentaPorCobrar::create([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'monto_original' => $venta->total,
                    'monto_pendiente' => $venta->total,
                    'estado' => 'Pendiente',
                    'fecha_vencimiento' => now()->addDays($diasPlazo),
                ]);

                $venta->estado = 'pendiente_pago';
                $venta->save();
            }

            return $venta->load('detalles.producto', 'cliente', 'user', 'cuentaPorCobrar');
        });
    }

    /**
     * Determina el plazo máximo de crédito (en días) basado en los productos vendidos.
     * @param array $itemsCalculados
     * @return int
     */
    private function determinarPlazoCredito(array $itemsCalculados): int
    {
        // Plazo por defecto (30 días = 1 mes)
        $plazoDefault = 30;
        $plazoCelulares = 180; // 6 meses

        $productoIds = collect($itemsCalculados)->pluck('producto_id')->all();

        //  Obtener las categorías de los productos vendidos        
        $productosConCategoria = Producto::whereIn('id', $productoIds)
            ->with('categoria')
            ->get();

        // Verificar si alguno de los productos pertenece a la categoría 'Celulares'
        $esCelular = $productosConCategoria->contains(function ($producto) {
            return optional($producto->categoria)->nombre === 'Celulares';
        });

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
        $ivaPorcentajeDB = $ivaPorcentajeInput / 100.0; // Convertir a factor (e.g., 19 -> 0.19)
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

            // Datos que se guardan a nivel de detalle
            $itemsCalculados[] = [
                'producto_id' => $item['producto_id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalNetoItem, // Subtotal neto por ítem

                // Datos Históricos
                'nombre_producto' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'precio_costo' => $producto->precio_compra,
                'descuento_monto' => $descuentoLineaMonto,

                // El IVA por ítem se calculará globalmente después del descuento total
                'iva_porcentaje' => 0.0, // Se inicializa a cero para el detalle
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
            'subtotal' => $subtotalVenta, // Subtotal neto de ítems antes del descuento global
            'descuento_total' => $descuentoTotalAcumulado,
            'iva_porcentaje' => $ivaPorcentajeFinal,
            'iva_monto' => $ivaMontoFinal,
            'total' => $totalFinal,
            'items' => $itemsCalculados, // Ítems sin IVA, se aplica el IVA en la cabecera
        ];
    }
}