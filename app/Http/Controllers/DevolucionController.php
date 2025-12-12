<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDevolucionRequest;
use App\Http\Resources\DevolucionResource;
use App\Http\Resources\DevolucionShowResource;
use App\Models\Devolucion;
use App\Models\Venta;
use App\Services\DevolucionService; // <-- Importación del servicio
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DevolucionController extends Controller
{
    private DevolucionService $devolucionService;

    // 1. Inyección de dependencia del servicio
    public function __construct(DevolucionService $devolucionService)
    {
        $this->devolucionService = $devolucionService;
    }

    /**
     * Registra una nueva devolución de productos (parcial), delegando la lógica de negocio al servicio.
     *
     * @param  \App\Http\Requests\StoreDevolucionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDevolucionRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $venta = Venta::findOrFail($validatedData['venta_id']);

            // 1. CHEQUEO DE ESTADO (Validación básica)
            if ($venta->estado === 'cancelada' || $venta->estado === 'reembolsada') {
                return response()->json([
                    'message' => 'Error: La venta ya fue cancelada/reembolsada y no acepta devoluciones.',
                ], 409);
            }

            // 2. DELEGACIÓN DE LÓGICA DE NEGOCIO (El servicio maneja la transacción DB)
            // Usamos 'SaldoCliente' como método de reembolso por defecto si no viene especificado.
            $metodoReembolso = $validatedData['metodo_reembolso'] ?? 'SaldoCliente';

            $ventaActualizada = $this->devolucionService->procesarDevolucionParcial(
                venta: $venta,
                itemsDevueltos: $validatedData['items_devueltos'],
                metodoReembolso: $metodoReembolso
            );

            // 3. RESPUESTA
            // Cargamos las devoluciones de auditoría recién creadas
            $nuevasDevoluciones = Devolucion::where('venta_id', $venta->id)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->with([
                    'producto',
                    'cliente',
                    'detalleVenta',
                    'venta.cuentaPorCobrar' // <-- Necesario para inferir la gestión financiera
                ])
                ->get();

            return response()->json([
                'message' => 'Devolución parcial procesada exitosamente. Se aplicó: ' . ($metodoReembolso === 'SaldoCliente' ? 'Nota Crédito/Saldo Cliente.' : 'Egreso de Caja.'),
                'venta_id' => $ventaActualizada->id,
                'nuevo_estado' => $ventaActualizada->estado,
                'devoluciones_creadas' => DevolucionResource::collection($nuevasDevoluciones)
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al procesar la devolución parcial: ' . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            return response()->json(['message' => 'Error al procesar la devolución parcial: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Anula una venta completa, revirtiendo todas las transacciones asociadas (Inventario, Cartera, Caja).
     *
     * @param  \Illuminate\Http\Request  $request Debe incluir 'motivo' y, opcionalmente, 'metodo_reembolso'.
     * @param  int  $ventaId
     * @return \Illuminate\Http\JsonResponse
     */
    public function anularVenta(Request $request, int $ventaId)
    {
        $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
            // Validar que el método de reembolso, si existe, sea válido
            'metodo_reembolso' => ['nullable', 'string', 'in:Efectivo,Transferencia,SaldoCliente'],
        ]);

        try {
            $venta = Venta::findOrFail($ventaId);

            $motivo = $request->input('motivo');
            $metodoReembolso = $request->input('metodo_reembolso'); // SaldoCliente por defecto

            $ventaAnulada = $this->devolucionService->anularVenta(
                venta: $venta,
                motivo: $motivo,
                metodoReembolso: $metodoReembolso
            );

            $mensajeReembolso = $metodoReembolso === 'SaldoCliente' ? 'Se generó una Nota Crédito/Saldo Cliente.' : "Se registró un egreso de caja por {$metodoReembolso}.";

            return response()->json([
                'message' => "Venta ID {$ventaId} anulada y revertida exitosamente. {$mensajeReembolso}",
                'venta_id' => $ventaAnulada->id,
                'nuevo_estado' => $ventaAnulada->estado,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al anular la venta: ' . $e->getMessage(), ['exception' => $e, 'venta_id' => $ventaId]);
            return response()->json(['message' => 'Error al anular la venta: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Obtiene todos los registros de Devolucion con estado_gestion = 'Pendiente',
     * incluyendo el proveedor del producto.
     */
    public function getPendientes(Request $request)
    {
        $devolucionesPendientes = Devolucion::where('estado_gestion', 'Pendiente')
            // Precarga el producto Y su proveedor anidado
            ->with(['producto.proveedores', 'cliente'])
            // Eliminamos 'venta' y 'detalleVenta' porque no los necesitamos en el frontend
            ->get();

        // Usamos la Colección de Resources para transformar los datos
        return DevolucionShowResource::collection($devolucionesPendientes);
    }

    /**
     * Actualiza el estado_gestion de una Devolucion específica.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'estado_gestion' => ['required', 'string', 'in:Pendiente,Contactado,Finalizada'],
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
