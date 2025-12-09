<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDevolucionRequest;
use App\Http\Resources\DevolucionResource;
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
     * Registra una nueva devolución de productos, delegando la lógica de negocio al servicio.
     *
     * @param  \App\Http\Requests\StoreDevolucionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreDevolucionRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $venta = Venta::findOrFail($validatedData['venta_id']);

            // 1. CHEQUEO DE ESTADO (Validación)
            if ($venta->estado === 'cancelada' || $venta->estado === 'reembolsada') {
                return response()->json([
                    'message' => 'Error: La venta ya fue cancelada/reembolsada y no acepta devoluciones.',
                ], 409);
            }

            // 2. DELEGACIÓN DE LÓGICA DE NEGOCIO (El servicio maneja la transacción DB)
            $ventaActualizada = $this->devolucionService->procesarDevolucionParcial(
                venta: $venta,
                itemsDevueltos: $validatedData['items_devueltos'],
                metodoReembolso: $validatedData['metodo_reembolso'] ?? 'Efectivo'
            );

            // 3. RESPUESTA
            // Cargamos las devoluciones recién creadas si las necesitamos para el response
            $nuevasDevoluciones = Devolucion::where('venta_id', $venta->id)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->get();

            return response()->json([
                'message' => 'Devolución procesada exitosamente. Estado de venta: ' . $ventaActualizada->estado,
                'venta_id' => $ventaActualizada->id,
                'nuevo_estado' => $ventaActualizada->estado,
                'devoluciones_creadas' => DevolucionResource::collection($nuevasDevoluciones)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al procesar la devolución: ' . $e->getMessage(), ['exception' => $e, 'request_data' => $request->all()]);
            // El servicio lanzará la excepción, la cual revierte la transacción.
            return response()->json(['message' => 'Error al procesar la devolución: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene todos los registros de Devolucion con estado_gestion = 'Pendiente'.
     */
    public function getPendientes(Request $request)
    {
        $devolucionesPendientes = Devolucion::where('estado_gestion', 'Pendiente')
            ->with(['producto', 'cliente', 'venta', 'detalleVenta']) // Added detalleVenta
            ->get();

        return response()->json($devolucionesPendientes);
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