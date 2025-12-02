<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Resources\VentaIndexResource;
use App\Http\Resources\VentaShowResource;
use App\Models\Venta;
use App\Services\VentaService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión de Ventas (Facturas/Tiquetes POS).
 */
class VentaController extends Controller
{
    private VentaService $ventaService;

    public function __construct(VentaService $ventaService)
    {
        // Inyección de Dependencia del Servicio
        $this->ventaService = $ventaService;
    }

    /**
     * Muestra una lista de ventas con filtros, paginación y manejo de soft deletes (CRUD READ - INDEX).
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Inicializar la consulta y cargar las relaciones necesarias
        $query = Venta::with(['user', 'cliente', 'detalles.producto'])
            ->latest(); // Usamos latest() para order_by='created_at' desc

        // ----------------------------------------------------
        // *** CORRECCIÓN CLAVE PARA EL DASHBOARD ***
        // ----------------------------------------------------
        // Si se pide un 'limit' (como lo hace el servicio de Front-end con limit=10),
        // devolvemos un simple array sin paginar, optimizado para el dashboard.
        if ($limit = $request->get('limit')) {
            // Aseguramos que el límite sea un entero positivo, máximo 100
            $limit = min(abs((int) $limit), 100);

            // Aplicamos el límite y obtenemos la colección de resultados
            $ventas = $query->limit($limit)->get();

            // Usamos el Resource Collection y devolvemos el array directo (como lo espera tu Front)
            return response()->json(VentaIndexResource::collection($ventas));
        }
        // ----------------------------------------------------

        // 2. FILTRO: Buscar por Cliente, Fecha, etc. (El resto de filtros se mantiene igual)
        if ($searchCliente = $request->get('cliente')) {
            // ... (Tu lógica de filtro de cliente)
        }

        if ($fecha = $request->get('fecha')) {
            // ... (Tu lógica de filtro de fecha)
        } elseif ($fechaInicio = $request->get('fecha_inicio')) {
            // ... (Tu lógica de rango de fechas)
        }

        // 3. SOFT DELETES:
        if ($request->get('trashed') === 'with') {
            $query->withTrashed();
        } elseif ($request->get('trashed') === 'only') {
            $query->onlyTrashed();
        }

        // 4. PAGINACIÓN ESTÁNDAR (Solo si NO se solicitó un 'limit')
        $perPage = $request->get('per_page', 15);
        $ventas = $query->paginate($perPage);

        // Usamos el Resource y devolvemos la respuesta Paginada
        return response()->json($ventas);
        // NOTA: Si usas VentaIndexResource::collection($ventas) aquí, Laravel Resources
        //        encapsula la paginación. Si quieres el objeto de paginación completo,
        //        devuelve directamente response()->json($ventas);
    }

    /**
     * Almacena una nueva venta (CRUD CREATE - STORE).
     *
     * @param  \App\Http\Requests\StoreVentaRequest  $request
     */
    public function store(StoreVentaRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = auth()->id() ?? 1; // Asigna el user_id autenticado

        try {
            // La lógica de registro de venta, creación de detalles, actualización de
            // inventario, y manejo de cartera debe estar centralizada y manejada en una transacción 
            // de base de datos dentro del servicio.
            $venta = $this->ventaService->registrarVenta($validatedData);

            return response()->json([
                'message' => 'Venta registrada con éxito. Inventario actualizado.',
                'venta' => VentaShowResource::make($venta->load('detalles.producto', 'cartera')),
            ], 201);

        } catch (StockInsuficienteException $e) {
            // Manejo específico si el inventario falla la validación
            return response()->json(['error' => $e->getMessage()], 409); // 409 Conflict

        } catch (Exception $e) {
            // Manejo genérico de otros errores de negocio o DB
            return response()->json(['error' => 'Error al procesar la venta: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Muestra una venta específica (CRUD READ - SHOW).
     *
     * @param  \App\Models\Venta  $venta
     */
    public function show(Venta $venta): JsonResponse
    {
        // Aseguramos la carga de todas las relaciones aquí
        $venta->load(['detalles.producto', 'cartera', 'cliente', 'user']);

        // Usamos el Resource completo para el detalle
        return response()->json(VentaShowResource::make($venta));
    }

    /**
     * Actualiza una venta específica (CRUD UPDATE - UPDATE).
     *
     * @param  \App\Http\Requests\UpdateVentaRequest  $request
     * @param  \App\Models\Venta  $venta
     */
    public function update(UpdateVentaRequest $request, Venta $venta): JsonResponse
    {
        $venta->update($request->validated());

        return response()->json([
            'message' => 'Venta actualizada correctamente.',
            'venta' => VentaShowResource::make($venta),
        ]);
    }

    /**
     * Elimina una venta (Soft Delete).
     *
     * @param  \App\Models\Venta  $venta
     */
    public function destroy(Venta $venta): JsonResponse
    {
        // Idealmente, el servicio de venta debería manejar la reversión 
        // de inventario y la creación de notas de crédito/ajustes de cartera aquí.
        // Aquí solo se usa SoftDelete (la reversión debería ocurrir antes de la eliminación).
        $venta->delete();

        return response()->json(['message' => 'Venta eliminada (soft deleted) con éxito.'], 204);
    }

    /**
     * Restaura una venta eliminada suavemente.
     *
     * @param  int  $id
     */
    public function restore(int $id): JsonResponse
    {
        $venta = Venta::onlyTrashed()->findOrFail($id);
        $venta->restore();

        return response()->json([
            'message' => 'Venta restaurada con éxito.',
            'venta' => VentaIndexResource::make($venta->load('detalles.producto', 'cliente', 'user')),
        ]);
    }

    /**
     * Elimina permanentemente una venta.
     *
     * @param  int  $id
     */
    public function forceDelete(int $id): JsonResponse
    {
        $venta = Venta::onlyTrashed()->findOrFail($id);
        $venta->forceDelete();

        return response()->json(['message' => 'Venta eliminada permanentemente.'], 204);
    }
}