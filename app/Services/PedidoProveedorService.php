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


    public function createPedidoProveedor(array $data): PedidoProveedor
    {
        DB::beginTransaction();
        try {
            // CORRECCIÓN 1: Obtener el ID del usuario desde el Auth, no desde el Request
            $userId = Auth::id();

            if (!$userId) {
                throw new \Exception('Usuario no autenticado.');
            }

            // CORRECCIÓN 2: Validar fecha_entrega antes de insertar
            if (!isset($data['fecha_entrega']) || empty($data['fecha_entrega'])) {
                throw new \Exception('La fecha de entrega es obligatoria para recibir un pedido.');
            }

            // 1. Crear PedidoProveedor con estado recibido
            $pedido = PedidoProveedor::create([
                'numero_factura_proveedor' => $data['numero_factura_proveedor'] ?? 'S/N',
                'fecha_entrega' => $data['fecha_entrega'],
                'user_id' => $userId, // Usamos la variable segura
                'proveedor_id' => $data['proveedor_id'],
                'monto_total' => $data['monto_total'],
                'estado' => 'recibido',
            ]);

            // Obtener tipos de movimiento
            $tipoMovInvCompra = TipoMovimientoInventario::where('nombre', 'Compra')->firstOrFail();
            $tipoMovFinEgreso = TipoMovimientoFinanciero::where('nombre', 'Compra de Productos')
                ->where('tipo', 'Egreso')
                ->firstOrFail();

            // 2. Procesar productos
            foreach ($data['productos'] as $productoData) {
                $producto = Producto::findOrFail($productoData['producto_id']);
                $costo = $productoData['precio_compra'];
                $cantidad = $productoData['cantidad'];
                $subtotal = $cantidad * $costo;

                // Registro Histórico
                DetallePedidoProveedor::create([
                    'pedido_proveedor_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_compra' => $costo,
                    'subtotal' => $subtotal,
                ]);

                // Actualizar Producto
                $producto->increment('stock_actual', $cantidad);
                $producto->precio_compra = $costo;

                // Actualizar precio de venta (Mantenemos tu lógica de margen)
                $producto->precio_venta = $costo * (1 + self::MARGEN_BENEFICIO);
                $producto->save();

                // Movimiento Inventario
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'user_id' => $userId,
                    'tipo_movimiento_id' => $tipoMovInvCompra->id,
                    'cantidad' => $cantidad,
                    'costo_unitario' => $costo,
                    'referencia_tabla' => 'pedido_proveedores',
                    'referencia_id' => $pedido->id,
                ]);
            }

            // 3. Finanzas y Caja
            // Buscamos la caja del usuario logueado
            $cajaDiaria = CajaDiaria::where('user_id', $userId)
                ->where('estado', 'abierta')
                ->first();

            if (!$cajaDiaria) {
                throw new \Exception('Debe tener una caja abierta para registrar el egreso del pago.');
            }

            MovimientoFinanciero::create([
                'tipo_movimiento_id' => $tipoMovFinEgreso->id,
                'monto' => $data['monto_total'],
                'tipo' => 'Egreso',
                'descripcion' => 'Pago Factura #' . $pedido->numero_factura_proveedor,
                'metodo_pago' => $data['metodo_pago'] ?? 'transferencia',
                'user_id' => $userId,
                'referencia_tabla' => 'pedido_proveedores',
                'referencia_id' => $pedido->id,
                'caja_diaria_id' => $cajaDiaria->id,
            ]);

            DB::commit();
            return $pedido;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en PedidoProveedorService@receiveOrder: ' . $e->getMessage());
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