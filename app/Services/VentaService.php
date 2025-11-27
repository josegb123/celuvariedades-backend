<?php

namespace App\Services;

use App\Models\Cartera;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\TipoVenta;
use App\Models\Venta;
use Exception;
use Illuminate\Support\Facades\DB;

class VentaService
{
    private InventarioService $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Procesa una venta completa, actualiza inventario y genera cartera si es necesario.
     *
     * @param  array  $validatedData  Datos validados del VentaStoreRequest.
     */
    public function registrarVenta(array $validatedData): Venta
    {
        // 1. Iniciar la Transacción (CRÍTICO: Garantiza la atomicidad)
        return DB::transaction(function () use ($validatedData) {

            // Obtener el tipo de venta (Contado, Crédito, Separe) para la lógica de control
            $tipoVenta = TipoVenta::findOrFail($validatedData['tipo_venta_id']);
            $items = $validatedData['items'];

            // 2. Pre-cálculo y Preparación de Datos
            $descuentoGlobalMonto = $validatedData['descuento_total'] ?? 0.00;
            $calculos = $this->calcularTotales($items, $descuentoGlobalMonto);


            $datosVenta = [
                'user_id' => $validatedData['user_id'],
                'cliente_id' => $validatedData['cliente_id'] ?? null,
                'tipo_venta_id' => $tipoVenta->id,
                'subtotal' => $calculos['subtotal'],
                'descuento_total' => $calculos['descuento_total'],
                'iva_porcentaje' => $calculos['iva_porcentaje'],
                'iva_monto' => $calculos['iva_monto'],
                'total' => $calculos['total'],
                'estado' => $tipoVenta->maneja_cartera ? 'pendiente_pago' : 'finalizada', // Estado inicial
                'metodo_pago' => $validatedData['metodo_pago'] ?? ($tipoVenta->maneja_cartera ? 'credito' : 'efectivo')
            ];

            // 3. Creación de la Cabecera de la Venta
            $venta = Venta::create($datosVenta);

            // 4. Procesamiento de Ítems (Detalles y Kárdex)
            foreach ($calculos['items'] as $itemCalculado) {

                // Creación del Detalle de Venta
                $detalle = DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $itemCalculado['producto_id'],
                    'cantidad' => $itemCalculado['cantidad'],
                    'precio_unitario' => $itemCalculado['precio_unitario'],
                    'subtotal' => $itemCalculado['subtotal'],

                    // CAMPOS HISTÓRICOS Y DE COSTO
                    'nombre_producto' => $itemCalculado['nombre_producto'],
                    'codigo_barra' => $itemCalculado['codigo_barra'],
                    'precio_costo' => $itemCalculado['precio_costo'], // Costo del producto
                    'iva_porcentaje' => $itemCalculado['iva_porcentaje'],
                    'iva_monto' => $itemCalculado['iva_monto'],
                    'descuento_monto' => $itemCalculado['descuento_monto'], // Descuento de línea
                ]);

                // Actualización del Inventario (Delegado)
                // Usamos 1 ('Venta') para Salida final y 7 ('Transferencia Salida') si es para Plan Separe
                $tipoMovimientoNombre = $tipoVenta->nombre === 'Plan Separe' ? 'Transferencia Salida' : 'Venta';

                $this->inventarioService->ajustarStock(
                    productoId: $itemCalculado['producto_id'],
                    cantidad: $itemCalculado['cantidad'],
                    tipoMovimientoNombre: $tipoMovimientoNombre, // Usamos nombre para flexibilidad
                    costoUnitario: $itemCalculado['precio_costo'], // Pasamos el costo
                    userId: $venta->user_id,
                    referenciaTabla: 'ventas',
                    referenciaId: $venta->id
                );
            }

            // 5. Gestión de Cartera (Cuentas por Cobrar)
            if ($tipoVenta->maneja_cartera) {
                if (!$venta->cliente_id) {
                    throw new Exception('Una venta a crédito/separe requiere un cliente.');
                }

                Cartera::create([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'monto_original' => $venta->total,
                    'monto_pendiente' => $venta->total, // Asumimos que todo el valor queda como pendiente
                    'estado_deuda' => 'Pendiente',
                    // La fecha de vencimiento debe ser calculada aquí (no implementada para brevedad)
                ]);
                $venta->estado = 'pendiente_pago';
                $venta->save();
            }

            return $venta->load('detalles.producto', 'cliente', 'user');
        });
    }


    /**
     * Consulta precios y calcula el subtotal, IVA y total de la venta.
     *
     * @param  array  $items  Array de productos y cantidades.
     * @param  float  $descuentoGlobal  Descuento a aplicar al total.
     */
    private function calcularTotales(array $items, float $descuentoGlobalMonto): array
    {
        $subtotalVenta = 0;
        $descuentoTotalAcumulado = 0;
        $ivaMontoTotal = 0;
        $ivaPorcentajeBase = 0.19; // 19%
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

            $ivaMontoItem = $subtotalNetoItem * $ivaPorcentajeBase;

            $subtotalVenta += $subtotalNetoItem;
            $descuentoTotalAcumulado += $descuentoLineaMonto;
            $ivaMontoTotal += $ivaMontoItem;

            $itemsCalculados[] = [
                'producto_id' => $item['producto_id'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalNetoItem,

                // Datos Históricos
                'nombre_producto' => $producto->nombre,
                'codigo_barra' => $producto->codigo_barra,
                'precio_costo' => $producto->precio_compra,
                'descuento_monto' => $descuentoLineaMonto,
                'iva_porcentaje' => $ivaPorcentajeBase * 100,
                'iva_monto' => $ivaMontoItem,
            ];
        }

        // Aplicación del descuento global (sobre el Subtotal Neto Acumulado)
        $subtotalNetoGlobal = $subtotalVenta - $descuentoGlobalMonto;
        $ivaMontoFinal = $subtotalNetoGlobal * $ivaPorcentajeBase;

        $totalFinal = $subtotalNetoGlobal + $ivaMontoFinal;

        // El descuento total de la venta es la suma de descuentos de línea + descuento global.
        $descuentoTotalAcumulado += $descuentoGlobalMonto;

        return [
            'subtotal' => $subtotalVenta, // Subtotal neto de ítems (base para el IVA)
            'descuento_total' => $descuentoTotalAcumulado,
            'iva_porcentaje' => $ivaPorcentajeBase * 100,
            'iva_monto' => $ivaMontoFinal,
            'total' => $totalFinal,
            'items' => $itemsCalculados,
        ];
    }
}
