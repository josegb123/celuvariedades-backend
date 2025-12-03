<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimientoFinancieroRequest;
use App\Http\Resources\MovimientoFinancieroResource;
use App\Services\MovimientoFinancieroService;
use App\Models\MovimientoFinanciero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class MovimientoFinancieroController extends Controller
{
    private MovimientoFinancieroService $movimientoFinancieroService;

    public function __construct(MovimientoFinancieroService $movimientoFinancieroService)
    {
        $this->movimientoFinancieroService = $movimientoFinancieroService;
    }

    /**
     * Muestra el listado paginado de movimientos financieros.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // 1. Filtrado y ordenación
        $query = MovimientoFinanciero::query()
            ->with(['tipoMovimiento', 'user']) // Eager loading
            ->orderByDesc('created_at');

        // 2. Aplicar filtros opcionales (ej: por tipo o rango de fechas)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo); // 'Ingreso' o 'Egreso'
        }
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [$request->fecha_inicio, $request->fecha_fin . ' 23:59:59']);
        }

        $movimientos = $query->paginate(20);

        return MovimientoFinancieroResource::collection($movimientos);
    }

    /**
     * Registra un nuevo movimiento financiero (ej. Gasto Operacional Vario).
     *
     * @param StoreMovimientoFinancieroRequest $request
     * @return MovimientoFinancieroResource|JsonResponse
     */
    /**
     * Registra un nuevo movimiento financiero (ej. Gasto Operacional Vario).
     */
    public function store(StoreMovimientoFinancieroRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Los movimientos manuales no tienen una referencia ID/Tabla o Venta ID
            $referenciaTabla = $validatedData['referencia_tabla'] ?? 'manual';
            $referenciaId = $validatedData['referencia_id'] ?? 0;

            $movimiento = $this->movimientoFinancieroService->registrarMovimiento(
                monto: $validatedData['monto'],
                tipoMovimientoNombre: $validatedData['tipo_movimiento_nombre'],
                metodoPago: $validatedData['metodo_pago'],
                userId: $validatedData['user_id'],
                descripcion: $validatedData['descripcion'],
                referenciaTabla: $referenciaTabla,
                referenciaId: $referenciaId,

            );

            // Cargar relaciones para el resource
            $movimiento->load('tipoMovimiento', 'user');
            return new MovimientoFinancieroResource($movimiento);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al registrar movimiento financiero',
                'message' => $e->getMessage(),
            ], 400);
        }

    }

    /**
     * Muestra un movimiento financiero específico.
     *
     * @param MovimientoFinanciero $movimientoFinanciero
     * @return MovimientoFinancieroResource
     */
    public function show(MovimientoFinanciero $movimientoFinanciero): MovimientoFinancieroResource
    {
        // Cargar las relaciones necesarias antes de pasar al resource
        $movimientoFinanciero->load('tipoMovimiento', 'user');

        return new MovimientoFinancieroResource($movimientoFinanciero);
    }

    // Los métodos update y destroy no son recomendados para transacciones financieras 
    // por razones de auditoría. Se deben crear movimientos de 'corrección' o 'reversión'.
}