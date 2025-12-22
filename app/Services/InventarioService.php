<?php

// app/Services/InventarioService.php

namespace App\Services;

use App\Http\Exceptions\StockInsuficienteException;
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
            // Mantener lógica simple, ya que 'Transferencia Salida' es el caso de reserva
            $esPlanSepare = ($tipoMovimientoNombre === 'Transferencia Salida');

            // La columna que se usa para la VALIDACIÓN de disponibilidad.
            $columnaValidacion = $esPlanSepare ? 'stock_actual' : 'stock_actual'; // Para Plan Separe, se valida contra el stock_actual antes de moverlo

            // 3. Validación de Stock
            if ($esSalida) {
                if ($producto->{$columnaValidacion} < $cantidad) {
                    // Usamos la excepción customizada con datos relevantes
                    throw new StockInsuficienteException(
                        productoId: $productoId,
                        cantidadSolicitada: $cantidad,
                        cantidadDisponible: $producto->{$columnaValidacion},
                        message: "Stock insuficiente para el producto {$producto->nombre} en {$columnaValidacion}."
                    );
                }
            }

            // 4. Actualización del Stock (bloqueado con lockForUpdate)
            if ($esPlanSepare) {
                // CASO PLAN SEPARE: MOVIMIENTO DE STOCK ACTUAL A RESERVADO
                // 1. Reducir stock_actual
                $producto->decrement('stock_actual', $cantidad);
                // 2. Aumentar stock_reservado
                $producto->increment('stock_reservado', $cantidad);

            } elseif ($esSalida) {
                // CASO VENTA (Contado/Crédito): SALIDA de stock_actual
                $producto->decrement('stock_actual', $cantidad);
            } else {
                // CASO ENTRADA (Compra/Ajuste Positivo): ENTRADA a stock_actual
                $producto->increment('stock_actual', $cantidad);
            }

            // 5. Recargar y Guardar Timestamps            
            $producto = $producto->fresh();

            // 5. Registro en el Kárdex (MovimientoInventario)
            $this->movimientoModel->create([
                'producto_id' => $productoId,
                'user_id' => $userId,
                'tipo_movimiento_id' => $tipoMovimiento->id, // Usamos el ID encontrado
                'cantidad' => $cantidad,
                'costo_unitario' => $costoUnitario, // Registramos el costo en el Kárdex
                'referencia_tabla' => $referenciaTabla,
                'referencia_id' => $referenciaId,
            ]);

            return $producto;
        });
    }
}
