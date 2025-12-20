<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Resources\VentaIndexResource;
use App\Http\Resources\VentaShowResource;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $user = auth()->user();

        $query = Venta::with(['user', 'cliente', 'detalles.producto'])
            ->latest();

        // 1. FILTRO DE SEGURIDAD POR ROL
        if ($user->role === 'seller') {
            $query->where('user_id', $user->id);
        }

        // 2. FILTRO POR FECHA (Hoy por defecto)
        // Solo aplica 'hoy' si no se envían filtros específicos de fecha
        if (!$request->has('fecha') && !$request->has('fecha_inicio') && !$request->has('all_time')) {
            $query->whereDate('created_at', now()->today());
        }

        // 3. FILTROS DINÁMICOS
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('cliente', fn($c) => $c->where('nombre', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        if ($metodoPago = $request->get('metodo_pago')) {
            $query->where('metodo_pago', $metodoPago);
        }

        if ($clienteId = $request->get('cliente_id')) {
            $query->where('cliente_id', $clienteId);
        }

        // Rango de fechas explícito
        if ($fecha = $request->get('fecha')) {
            $query->whereDate('created_at', $fecha);
        } elseif ($fechaInicio = $request->get('fecha_inicio')) {
            $fechaFin = $request->get('fecha_fin', now()->toDateString());
            $query->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio, $fechaFin]);
        }

        // 4. SOFT DELETES
        if ($request->get('trashed') === 'with') {
            $query->withTrashed();
        } elseif ($request->get('trashed') === 'only') {
            $query->onlyTrashed();
        }

        // 5. RESPUESTA SEGÚN LÍMITE O PAGINACIÓN
        if ($limit = $request->get('limit')) {
            $limit = min(abs((int) $limit), 100);
            $ventas = $query->limit($limit)->get();
            return response()->json(VentaIndexResource::collection($ventas));
        }

        $perPage = $request->get('per_page', 15);
        $ventas = $query->paginate($perPage);

        return VentaIndexResource::collection($ventas)->response();
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
            // inventario, y manejo de saldo debe estar centralizada y manejada en una transacción 
            // de base de datos dentro del servicio.
            $venta = $this->ventaService->registrarVenta($validatedData);

            return response()->json([
                'message' => 'Venta registrada con éxito. Inventario actualizado.',
                'venta' => VentaShowResource::make($venta->load('detalles.producto', )),
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
        $venta->load(['detalles.producto', 'cliente', 'user']);

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

    /**
     * Genera y streamea la factura POS en formato PDF para impresión.
     *
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Http\Response (un PDF stream)
     */
    public function imprimirFacturaPos(Venta $venta)
    {
        // Cargar las relaciones necesarias
        $venta->load(['detalles.producto', 'cliente', 'user']); // Ensure product relation is loaded for details

        // Aseguramos que los atributos como subtotal, iva_monto, total estén disponibles
        $venta->refresh(); // Ensures the latest state of the model from the database

        // --- CÁLCULO DE ALTURA DINÁMICA (en puntos) ---

        // 1. Altura fija (Header, Footer, Detalles, Totales)
        $alturaFijaPuntos = 350;

        // 2. Altura variable por ítem (12 puntos por línea de producto)
        $alturaPorDetalle = 20;
        $conteoDetalles = $venta->detalles->count();

        // 3. Altura total requerida
        $alturaTotalPuntos = $alturaFijaPuntos + ($conteoDetalles * $alturaPorDetalle);

        // ⚠️ OJO: Agregar un margen extra de seguridad (ej. 30 puntos)
        $alturaTotalPuntos += 30;

        // --- CONFIGURACIÓN DE PAPEL ---

        // Ancho fijo de 80mm = 226.77 puntos
        $anchoPosPuntos = 226.77;

        // Usamos el array(0, 0, ancho, alto) para definir el tamaño
        // El "0, 0" define la coordenada de inicio, no es parte de la dimensión.
        $tamanoPapel = [0, 0, $anchoPosPuntos, $alturaTotalPuntos];

        // 2. Generar el PDF
        $pdf = Pdf::loadView('pdfs.factura_celuvariedades_pos', compact('venta'))
            // Aplicar TAMAÑO DE PAPEL y ORIENTACIÓN
            ->setPaper($tamanoPapel, 'portrait');

        // 3. Devolver la respuesta en streaming
        return $pdf->stream("factura-celuvariedades-pos-{$venta->id}.pdf");
    }

}