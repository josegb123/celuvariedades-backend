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

                // Crear DetallePedidoProveedor
                DetallePedidoProveedor::create([
                    'pedido_proveedor_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $productoData['cantidad'],
                    'precio_compra' => $productoData['precio_compra'],
                    'subtotal' => $productoData['cantidad'] * $productoData['precio_compra'],
                ]);

                // Actualizar stock del Producto
                $producto->increment('stock_actual', $productoData['cantidad']);

                // Registrar MovimientoInventario
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'user_id' => $data['user_id'],
                    'tipo_movimiento_id' => $tipoMovimientoInventarioCompra->id,
                    'cantidad' => $productoData['cantidad'],
                    'costo_unitario' => $productoData['precio_compra'],
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
            throw $e; // Re-lanzar la excepción para que el controlador la maneje
        }
    }
}

