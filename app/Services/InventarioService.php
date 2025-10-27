<?php

// app/Services/InventarioService.php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\TipoMovimientoInventario;
use Exception;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    private Producto $productoModel;

    private MovimientoInventario $movimientoModel;

    public function __construct(Producto $productoModel, MovimientoInventario $movimientoModel)
    {
        $this->productoModel = $productoModel;
        $this->movimientoModel = $movimientoModel;
    }

    /**
     * Ajusta el stock (actual o reservado) de un producto y registra el movimiento en el Kárdex.
     */
    public function ajustarStock(
        int $productoId,
        int $cantidad,
        int $tipoMovimientoId,
        int $userId,
        string $referenciaTabla,
        int $referenciaId
    ): Producto {

        // 1. Iniciar la Transacción DB (CRÍTICO)
        return DB::transaction(function () use (
            $productoId,
            $cantidad,
            $tipoMovimientoId,
            $userId,
            $referenciaTabla,
            $referenciaId
        ) {
            $producto = $this->productoModel->lockForUpdate()->find($productoId);
            $tipoMovimiento = TipoMovimientoInventario::find($tipoMovimientoId);

            if (! $producto) {
                throw new Exception('Producto no encontrado.');
            }
            if (! $tipoMovimiento) {
                throw new Exception('Tipo de movimiento de inventario no válido.');
            }

            $esEntrada = $tipoMovimiento->tipo_operacion === 'ENTRADA';
            $afectaReserva = $tipoMovimiento->reserva_stock ?? false; // Nuevo campo de control si lo incluiste

            $columnaStock = $afectaReserva ? 'stock_reservado' : 'stock_actual';

            // 2. Validación de Stock (Solo para Salidas NO reservadas)
            if (! $esEntrada && ! $afectaReserva) {
                if ($producto->{$columnaStock} < $cantidad) {
                    throw new StockInsuficienteException("Stock insuficiente para el producto {$producto->nombre}. Disponible: {$producto->{$columnaStock}}.");
                }
            }

            // 3. Actualización del Stock
            if ($esEntrada) {
                $producto->increment($columnaStock, $cantidad);
            } else {
                $producto->decrement($columnaStock, $cantidad);
            }

            $producto->save(); // Guarda los cambios

            // 4. Registro en el Kárdex (MovimientoInventario)
            $this->movimientoModel->create([
                'producto_id' => $productoId,
                'user_id' => $userId,
                'tipo_movimiento_id' => $tipoMovimientoId,
                'cantidad' => $cantidad,
                'costo_unitario' => $producto->precio_compra, // Usamos precio_compra como costo base
                'referencia_tabla' => $referenciaTabla,
                'referencia_id' => $referenciaId,
            ]);

            return $producto;
        });
    }
}
