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
        float $cantidad, // Usamos float por si hay cantidades decimales (peso)
        string $tipoMovimientoNombre, // Usamos Nombre para la integración (ej: 'Venta', 'Transferencia Salida')
        float $costoUnitario, // Recibimos el costo del producto para el Kárdex
        int $userId,
        string $referenciaTabla,
        int $referenciaId
    ): Producto {

        return DB::transaction(function () use ($productoId, $cantidad, $tipoMovimientoNombre, $costoUnitario, $userId, $referenciaTabla, $referenciaId) {
            $producto = $this->productoModel->lockForUpdate()->find($productoId);

            // 1. Búsqueda de Tipo Movimiento por NOMBRE
            $tipoMovimiento = TipoMovimientoInventario::where('nombre', $tipoMovimientoNombre)->first();

            if (!$producto) {
                throw new Exception('Producto no encontrado.');
            }
            if (!$tipoMovimiento) {
                // Esto ayuda a atrapar errores en la configuración de los Seeders.
                throw new Exception("Tipo de movimiento de inventario '{$tipoMovimientoNombre}' no válido.");
            }

            $esSalida = $tipoMovimiento->tipo_operacion === 'SALIDA';

            // 2. Lógica para determinar si afecta 'stock_actual' o 'stock_reservado'
            // Solo 'Venta' afecta stock_actual. 'Transferencia Salida' (usado para Separe) afecta stock_reservado.
            $afectaReserva = ($tipoMovimientoNombre === 'Transferencia Salida');
            $columnaStock = $afectaReserva ? 'stock_reservado' : 'stock_actual';

            // 3. Validación de Stock
            if ($esSalida) {
                if ($producto->{$columnaStock} < $cantidad) {
                    throw new StockInsuficienteException(
                        productoId: $productoId,
                        cantidadSolicitada: $cantidad,
                        cantidadDisponible: $producto->{$columnaStock},
                        message: "Stock insuficiente para el producto {$producto->nombre} en {$columnaStock}."
                    );
                }
            }

            // 4. Actualización del Stock (bloqueado con lockForUpdate)
            if ($esSalida) {
                $producto->decrement($columnaStock, $cantidad);
            } else {
                $producto->increment($columnaStock, $cantidad);
            }

            $producto->save();

            // 5. Registro en el Kárdex (MovimientoInventario)
            $this->movimientoModel->create([
                'producto_id' => $productoId,
                'user_id' => $userId,
                'tipo_movimiento_id' => $tipoMovimiento->id, // Usamos el ID encontrado
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario, // CRÍTICO: Registramos el costo en el Kárdex
                'referencia_tabla' => $referenciaTabla,
                'referencia_id' => $referenciaId,
            ]);

            return $producto;
        });
    }
}
