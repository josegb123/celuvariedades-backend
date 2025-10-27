<?php

// app/Http/Controllers/VentaController.php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Requests\VentaStoreRequest;
use App\Models\Venta;
use App\Services\VentaService;
use Exception;
use Illuminate\Http\JsonResponse; // Asumiendo que creaste esta excepción

class VentaController extends Controller
{
    private VentaService $ventaService;

    // Inyección de Dependencia del Servicio
    public function __construct(VentaService $ventaService)
    {
        $this->ventaService = $ventaService;
    }

    /**
     * Muestra una lista de ventas (CRUD READ - INDEX).
     */
    public function index(): JsonResponse
    {
        // En una aplicación real, se usaría paginación y filtros
        $ventas = Venta::with(['detalles.producto', 'cliente', 'user'])->latest()->paginate(15);

        return response()->json($ventas);
    }

    /**
     * Almacena una nueva venta (CRUD CREATE - STORE).
     */
    public function store(StoreVentaRequest $request): JsonResponse
    {
        // El $request ya está validado gracias a VentaStoreRequest
        $validatedData = $request->validated();

        // 🚨 CRÍTICO: Aseguramos que el ID del usuario autenticado se use para la venta.
        // Asumiendo que tienes Auth en la API.
        $validatedData['user_id'] = auth()->id() ?? 1; // Usar 1 como fallback si no hay auth

        try {
            // Delegamos la lógica transaccional al servicio
            $venta = $this->ventaService->registrarVenta($validatedData);

            return response()->json([
                'message' => 'Venta registrada con éxito. Inventario actualizado.',
                'venta' => $venta->load('detalles.producto', 'cartera'),
            ], 201);

        } catch (StockInsuficienteException $e) {
            // Manejo específico si el inventario falla la validación
            return response()->json(['error' => $e->getMessage()], 409); // 409 Conflict

        } catch (Exception $e) {
            // Manejo genérico de otros errores de negocio (ej. falta de cliente para crédito)
            return response()->json(['error' => 'Error al procesar la venta: '.$e->getMessage()], 500);
        }
    }

    /**
     * Muestra una venta específica (CRUD READ - SHOW).
     */
    public function show(Venta $venta): JsonResponse
    {
        return response()->json($venta->load('detalles.producto', 'cartera', 'cliente'));
    }

    /**
     * Actualiza una venta específica (CRUD UPDATE - UPDATE).
     * Solo para campos no transaccionales (estado, método de pago).
     */
    public function update(UpdateVentaRequest $request, Venta $venta): JsonResponse
    {
        $venta->update($request->validated());

        return response()->json([
            'message' => 'Venta actualizada correctamente.',
            'venta' => $venta,
        ]);
    }

    /**
     * Elimina una venta (CRUD DELETE - DESTROY).
     * ⚠️ Peligroso: Una eliminación real requeriría una reversión compleja de inventario y cartera.
     * Usamos softDeletes, así que solo se oculta.
     */
    public function destroy(Venta $venta): JsonResponse
    {
        // Si necesitas revertir stock y cartera, debes llamar a un DevolucionService aquí.
        // Aquí solo se usa SoftDelete.
        $venta->delete();

        return response()->json(['message' => 'Venta eliminada (soft deleted) con éxito.'], 204);
    }
}
