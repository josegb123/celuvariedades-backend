<?php

namespace App\Services;

use App\Models\PedidoProveedor;
use App\Models\DetallePedidoProveedor;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\MovimientoFinanciero;
use App\Models\TipoMovimientoInventario;
use App\Models\TipoMovimientoFinanciero;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\CajaDiaria;

class PedidoProveedorService
{
    // Puedes definir una constante o cargarla de una configuración
    const MARGEN_BENEFICIO = 0.30; // 30% de margen (ejemplo)

    public function receiveOrder(array $data): PedidoProveedor
    {
        DB::beginTransaction();
        try {
            // 1. Crear PedidoProveedor
            $pedido = PedidoProveedor::create([
                'numero_factura_proveedor' => $data['numero_factura_proveedor'] ?? null,
                'fecha_entrega' => $data['fecha_entrega'],
                'user_id' => $data['user_id'],
                'proveedor_id' => $data['proveedor_id'],
                'monto_total' => $data['monto_total'],
                'estado' => 'recibido',
            ]);

            // Obtener tipos de movimiento para inventario y financiero
            $tipoMovimientoInventarioCompra = TipoMovimientoInventario::where('nombre', 'Compra')->firstOrFail();
            $tipoMovimientoFinancieroEgresoCompra = TipoMovimientoFinanciero::where('nombre', 'Compra de Productos')->where('tipo', 'Egreso')->firstOrFail();

            // 2. Procesar productos del pedido
            foreach ($data['productos'] as $productoData) {
                $producto = Producto::findOrFail($productoData['producto_id']);
                $nuevoCosto = $productoData['precio_compra'];
                $nuevaCantidad = $productoData['cantidad'];

                // Crear DetallePedidoProveedor (Registro Histórico)
                DetallePedidoProveedor::create([
                    'pedido_proveedor_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $nuevaCantidad,
                    'precio_compra' => $nuevoCosto,
                    'subtotal' => $nuevaCantidad * $nuevoCosto,
                ]);

                // 🎯 INICIO DE LA LÓGICA DE ACTUALIZACIÓN DE COSTO Y STOCK 🎯

                // 2.1. Actualizar stock del Producto (usando incremento, como ya lo tenías)
                $producto->increment('stock_actual', $nuevaCantidad);

                // 2.2. Actualizar precio_compra (Último Costo)
                $producto->precio_compra = $nuevoCosto;

                // 2.3. Calcular y actualizar precio_venta (Costo + Margen)

                // Si el margen está en el modelo Producto, usa:
                // $margen = $producto->margen_beneficio ?? self::MARGEN_BENEFICIO;

                // Usando la constante (margen fijo):
                $margen = self::MARGEN_BENEFICIO;
                $producto->precio_venta = $nuevoCosto * (1 + $margen);

                // 2.4. Guardar los cambios de precio y stock en la base de datos
                $producto->save();

                // 🎯 FIN DE LA LÓGICA DE ACTUALIZACIÓN DE COSTO Y STOCK 🎯

                // Registrar MovimientoInventario
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'user_id' => $data['user_id'],
                    'tipo_movimiento_id' => $tipoMovimientoInventarioCompra->id,
                    'cantidad' => $nuevaCantidad,
                    'costo_unitario' => $nuevoCosto,
                    'referencia_tabla' => 'pedido_proveedores',
                    'referencia_id' => $pedido->id,
                ]);
            }

            // 3. Registrar MovimientoFinanciero (Egreso)
            // Obtener la caja diaria activa del usuario que recibe el pedido
            $cajaDiaria = CajaDiaria::where('user_id', $data['user_id'])
                ->where('estado', 'abierta')
                ->first();

            if (!$cajaDiaria) {
                throw new \Exception('No se encontró una caja diaria abierta para el usuario que recibe el pedido.');
            }

            MovimientoFinanciero::create([
                'tipo_movimiento_id' => $tipoMovimientoFinancieroEgresoCompra->id,
                'monto' => $data['monto_total'],
                'tipo' => 'Egreso',
                'descripcion' => 'Pago por pedido a proveedor #' . ($data['numero_factura_proveedor'] ?? $pedido->id),
                'metodo_pago' => $data['metodo_pago'] ?? 'transferencia', // Asumiendo un método de pago por defecto
                'user_id' => $data['user_id'],
                'referencia_tabla' => 'pedido_proveedores',
                'referencia_id' => $pedido->id,
                'caja_diaria_id' => $cajaDiaria->id, // Asignar el ID de la caja diaria
            ]);

            DB::commit();
            return $pedido;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al recibir pedido: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}