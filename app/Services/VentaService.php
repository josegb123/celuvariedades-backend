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
            $calculos = $this->calcularTotales($items, $validatedData['descuento_total'] ?? 0);

            $datosVenta = [
                'user_id' => $validatedData['user_id'],
                'cliente_id' => $validatedData['cliente_id'] ?? null,
                'tipo_venta_id' => $tipoVenta->id,
                'subtotal' => $calculos['subtotal'],
                'descuento_total' => $calculos['descuento_aplicado'],
                'iva_porcentaje' => $calculos['iva_porcentaje'],
                'iva_monto' => $calculos['iva_monto'],
                'total' => $calculos['total'],
                'metodo_pago' => $validatedData['metodo_pago'] ?? 'efectivo',
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
                ]);

                // Actualización del Inventario mediante el Servicio
                $tipoMovimientoId = $this->getTipoMovimientoVentaId($tipoVenta);

                $this->inventarioService->ajustarStock(
                    productoId: $itemCalculado['producto_id'],
                    cantidad: $itemCalculado['cantidad'],
                    tipoMovimientoId: $tipoMovimientoId, // ID del tipo 'Venta' (Salida) o 'Plan Separe' (Reserva)
                    userId: $venta->user_id,
                    referenciaTabla: 'ventas',
                    referenciaId: $venta->id
                );
            }

            // 5. Gestión de Cartera (Cuentas por Cobrar)
            if ($tipoVenta->maneja_cartera) {
                if (! $venta->cliente_id) {
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

            return $venta;
        });
    }

    /**
     * Función para obtener el tipo de movimiento de Kárdex basado en el TipoVenta.
     */
    private function getTipoMovimientoVentaId(TipoVenta $tipoVenta): int
    {
        // En una implementación real, esto consultaría la tabla TipoMovimientoInventario
        // Aquí asumimos que ya tenemos los IDs de los tipos de movimiento básicos:
        if ($tipoVenta->reserva_stock) {
            // Plan Separe (Mueve a stock_reservado)
            return 3; // Suponiendo ID 3 para 'Salida por Plan Separe'
        }

        // Contado o Crédito (Mueve a stock_actual)
        return 1; // Suponiendo ID 1 para 'Salida por Venta'
    }

    /**
     * Consulta precios y calcula el subtotal, IVA y total de la venta.
     *
     * @param  array  $items  Array de productos y cantidades.
     * @param  float  $descuentoGlobal  Descuento a aplicar al total.
     */
    private function calcularTotales(array $items, float $descuentoGlobal): array
    {
        $subtotalVenta = 0;
        $ivaPorcentaje = 0.19; // 19%
        $itemsCalculados = [];

        // Consulta de precios de productos (evita manipulación)
        $productoIds = collect($items)->pluck('producto_id')->all();
        $productosDB = Producto::whereIn('id', $productoIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $producto = $productosDB->get($item['producto_id']);
            if (! $producto) {
                throw new Exception("Producto con ID {$item['producto_id']} no existe en la base de datos.");
            }

            $precioUnitario = $producto->precio_venta;
            $subtotalItem = $precioUnitario * $item['cantidad'];
            $subtotalVenta += $subtotalItem;

            $itemsCalculados[] = [
                'producto_id' => $item['producto_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalItem,
            ];
        }

        // Aplicación de descuento global
        $descuentoAplicado = $subtotalVenta * ($descuentoGlobal / 100); // Asumimos descuentoGlobal es un porcentaje
        $subtotalConDescuento = $subtotalVenta - $descuentoAplicado;

        // Cálculo de IVA y Total
        $ivaMonto = $subtotalConDescuento * $ivaPorcentaje;
        $total = $subtotalConDescuento + $ivaMonto;

        return [
            'subtotal' => $subtotalVenta, // Subtotal antes de descuento
            'descuento_aplicado' => $descuentoAplicado,
            'iva_porcentaje' => $ivaPorcentaje * 100,
            'iva_monto' => $ivaMonto,
            'total' => $total,
            'items' => $itemsCalculados,
        ];
    }
}
