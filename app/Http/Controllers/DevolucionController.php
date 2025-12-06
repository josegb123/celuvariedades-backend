<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDevolucionRequest;
use App\Http\Resources\DevolucionResource;
use App\Models\CajaDiaria;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Models\MovimientoFinanciero;
use App\Models\SaldoCliente;
use App\Models\TipoMovimientoFinanciero;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request; // Keep for potential other methods

class DevolucionController extends Controller
{
    /**
     * Registra una nueva devolución de productos.
     * El stock NO se incrementa, la venta se marca como 'reembolsada' y se generan movimientos financieros.
     *
     * @param  \App\Http\Requests\StoreDevolucionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDevolucionRequest $request)
    {
        // DB::transaction garantiza que todo se revierta si hay un error
        return DB::transaction(function () use ($request) {
            try {
                $validatedData = $request->validated();
                $ventaId = $validatedData['venta_id'];
                $productosDevueltos = $validatedData['productos_devueltos'];

                $venta = Venta::findOrFail($ventaId);

                // 1. CHEQUEO DE ESTADO (Anti-fraude)
                if ($venta->estado === 'reembolsada') {
                    return response()->json([
                        'message' => 'Error: La Venta #' . $ventaId . ' ya ha sido marcada como reembolsada y no puede ser procesada de nuevo.',
                        'venta_estado' => $venta->estado
                    ], 409);
                }

                $cliente = $venta->cliente ?? Cliente::findOrFail($request->cliente_id);
                $totalMontoDevuelto = 0;
                $devolucionesCreadas = [];

                foreach ($productosDevueltos as $item) {
                    // 2. CREAR DEVOLUCIÓN (Se revierte si la transacción falla)
                    $devolucion = Devolucion::create([
                        'venta_id' => $ventaId,
                        'producto_id' => $item['producto_id'],
                        'cliente_id' => $cliente->id,
                        'id_unico_producto' => $item['id_unico_producto'],
                        'cantidad' => $item['cantidad'],
                        'motivo' => $item['motivo'],
                        'costo_unitario' => $item['costo_unitario'],
                        'notas' => $item['notas'] ?? null,
                        'estado_gestion' => 'Pendiente',
                    ]);
                    $devolucionesCreadas[] = $devolucion;
                    $totalMontoDevuelto += ($item['cantidad'] * $item['costo_unitario']);
                }

                // 3. ANULAR VENTA (Se revierte si la transacción falla)
                $venta->estado = 'reembolsada';
                $venta->save();

                // 4. IMPACTO FINANCIERO (Uso de value() para eficiencia)
                $tipoEgreso = TipoMovimientoFinanciero::where('nombre', 'Reembolso a Cliente')->firstOrFail(); // Usar firstOrFail

                $caja_diaria_id = CajaDiaria::query()
                    ->where('user_id', auth()->id())
                    ->where('fecha_cierre', null)
                    ->value('id');

                if (is_null($caja_diaria_id)) {
                    // Opción: Lanzar excepción si es obligatorio tener una caja abierta
                    // throw new \Exception("Debe tener una Caja Diaria Abierta para procesar el reembolso.");
                    Log::warning('No se pudo asociar Movimiento Financiero a Caja Diaria abierta para usuario: ' . auth()->id());
                }

                MovimientoFinanciero::create([
                    'monto' => $totalMontoDevuelto,
                    'tipo_movimiento_id' => $tipoEgreso->id,
                    'tipo' => 'Egreso',
                    'venta_id' => $ventaId,
                    'user_id' => auth()->id(),
                    'caja_diaria_id' => $caja_diaria_id,
                    'descripcion' => 'Devolución de productos de la Venta #' . $ventaId,
                    'metodo_pago' => 'Transferencia',
                ]);

                SaldoCliente::create([
                    'cliente_id' => $cliente->id,
                    'monto_original' => $totalMontoDevuelto,
                    'monto_pendiente' => $totalMontoDevuelto,
                    'estado' => 'Activo',
                    'motivo' => 'Devolución de productos de la Venta #' . $ventaId,
                ]);

                // 5. RESPUESTA (Uso de API Resource)
                $devolucionesCollection = new EloquentCollection($devolucionesCreadas);

                return response()->json([
                    'message' => 'Devolución registrada exitosamente. Venta actualizada y saldo de cliente generado.',
                    'devoluciones' => DevolucionResource::collection($devolucionesCollection)
                ], 201);

            } catch (\Exception $e) {
                // El catch maneja cualquier error y la transacción lo revierte todo
                Log::error('Error al registrar la devolución: ' . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
                return response()->json(['message' => 'Error al procesar la devolución: ' . $e->getMessage()], 500);
            }
        });
    }

    /**
     * Obtiene todos los registros de Devolucion con estado_gestion = 'Pendiente'.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendientes(Request $request)
    {
        $devolucionesPendientes = Devolucion::where('estado_gestion', 'Pendiente')
            ->with(['producto', 'cliente', 'venta']) // Eager load relationships
            ->get();

        return response()->json($devolucionesPendientes);
    }

    /**
     * Actualiza el estado_gestion de una Devolucion específica.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID de la Devolucion
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'estado_gestion' => ['required', 'string', 'in:Pendiente,Contactado,Finalizada'], // Define allowed states
        ]);

        $devolucion = Devolucion::findOrFail($id);
        $devolucion->estado_gestion = $request->estado_gestion;
        $devolucion->save();

        return response()->json([
            'message' => 'Estado de devolución actualizado exitosamente.',
            'devolucion' => $devolucion->load(['producto', 'cliente', 'venta'])
        ]);
    }
}