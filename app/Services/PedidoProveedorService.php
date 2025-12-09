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
use Illuminate\Support\Facades\Auth; // Asegúrate de importar Auth

class PedidoProveedorService
{
    // Puedes definir una constante o cargarla de una configuración
    const MARGEN_BENEFICIO = 0.30; // 30% de margen (ejemplo)

    /**
     * Crea un nuevo pedido a proveedor con estado 'pendiente'.
     */
    public function createPedidoProveedor(array $data): PedidoProveedor
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id(); // Obtener el ID del usuario autenticado

            // 1. Crear PedidoProveedor con estado pendiente
            $pedido = PedidoProveedor::create([
                'proveedor_id' => $data['proveedor_id'],
                'user_id' => $userId, // El usuario que crea el pedido
                'monto_total' => 0, // Se calculará sumando los productos
                'estado' => 'pendiente', // Estado inicial
                'numero_factura_proveedor' => null, // Esto se llena al recibir
                'fecha_entrega' => null, // Esto se llena al recibir
            ]);

            $montoTotal = 0;
            // 2. Procesar productos del pedido
            foreach ($data['productos'] as $productoData) {
                $producto = Producto::findOrFail($productoData['producto_id']);
                $costoUnitario = $productoData['precio_compra'];
                $cantidad = $productoData['cantidad'];
                $subtotal = $cantidad * $costoUnitario;
                $montoTotal += $subtotal;

                // Crear DetallePedidoProveedor
                DetallePedidoProveedor::create([
                    'pedido_proveedor_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_compra' => $costoUnitario,
                    'subtotal' => $subtotal,
                ]);
            }

            // Actualizar el monto_total del pedido después de calcular todos los productos
            $pedido->monto_total = $montoTotal;
            $pedido->save();

            DB::commit();
            return $pedido;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear el pedido a proveedor: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }

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

    /**
     * Actualiza un pedido a proveedor existente.
     */
    public function updatePedidoProveedor(array $data, PedidoProveedor $pedido): PedidoProveedor
    {
        DB::beginTransaction();
        try {
            // Actualizar campos principales del pedido
            $pedido->fill($data);
            $pedido->save();

            // Si se incluyen productos, actualizar los detalles del pedido
            if (isset($data['productos'])) {
                // Eliminar detalles existentes y crear nuevos, o actualizar
                $pedido->detalles()->delete(); // Una estrategia simple: borrar y recrear

                $montoTotal = 0;
                foreach ($data['productos'] as $productoData) {
                    $producto = Producto::findOrFail($productoData['producto_id']);
                    $costoUnitario = $productoData['precio_compra'];
                    $cantidad = $productoData['cantidad'];
                    $subtotal = $cantidad * $costoUnitario;
                    $montoTotal += $subtotal;

                    DetallePedidoProveedor::create([
                        'pedido_proveedor_id' => $pedido->id,
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_compra' => $costoUnitario,
                        'subtotal' => $subtotal,
                    ]);
                }
                $pedido->monto_total = $montoTotal;
                $pedido->save();
            }

            DB::commit();
            return $pedido;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pedido: ' . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}