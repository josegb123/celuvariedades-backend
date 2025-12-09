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
        // 1. Inicializar la consulta y cargar las relaciones necesarias
        $query = Venta::with(['user', 'cliente', 'detalles.producto'])
            ->latest(); // Usamos latest() para order_by='created_at' desc

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

        // 2. FILTROS PRINCIPALES (Usados en la vista de Administración)

        // 2.1. FILTRO: Búsqueda global por Cliente o Vendedor (usado como 'search' en el Front)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                // Buscar por nombre de cliente
                $q->whereHas('cliente', function ($qCliente) use ($search) {
                    $qCliente->where('nombre', 'like', "%{$search}%");
                })
                    // O buscar por nombre de usuario/vendedor
                    ->orWhereHas('user', function ($qUser) use ($search) {
                        $qUser->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 2.2. FILTRO: Por Estado (finalizada, pendiente_pago, cancelada)
        if ($estado = $request->get('estado')) {
            $query->where('estado', $estado);
        }

        // 2.3. FILTRO: Por Método de Pago
        if ($metodoPago = $request->get('metodo_pago')) {
            $query->where('metodo_pago', $metodoPago);
        }

        // 2.4. FILTRO: Por Cliente específico (si se usa el campo 'cliente' en el query)
        if ($cliente = $request->get('cliente_id')) { // Usar ID es más robusto que el nombre
            $query->where('cliente_id', $cliente);
        }

        // 2.5. FILTRO: Por Fecha exacta (si se usa el campo 'fecha')
        if ($fecha = $request->get('fecha')) {
            $query->whereDate('created_at', $fecha);
        }

        // 2.6. FILTRO: Por Rango de Fechas (Si se usa 'fecha_inicio' y 'fecha_fin'
        //              en lugar de la lógica simplificada de $fechaInicio)
        elseif ($fechaInicio = $request->get('fecha_inicio')) {
            $fechaFin = $request->get('fecha_fin', now()->toDateString()); // Por defecto, hasta hoy

            $query->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio, $fechaFin]);
        }


        // 3. SOFT DELETES:
        if ($request->get('trashed') === 'with') {
            $query->withTrashed();
        } elseif ($request->get('trashed') === 'only') {
            $query->onlyTrashed();
        }

        // 4. PAGINACIÓN ESTÁNDAR (Solo si NO se solicitó un 'limit')
        $perPage = $request->get('per_page', 15);

        // Ejecutamos la paginación        
        $ventas = $query->paginate($perPage);

        // Opcional: Aplicar el Resource Collection al objeto paginado si necesitas modificar los datos en la paginación.
        // Si no necesitas modificar los campos de la paginación (ej. current_page, total, etc.):
        return VentaIndexResource::collection($ventas)->response();

        // Si necesitas que el objeto retornado sea el raw Laravel Pagination object (como lo espera tu Front):
        // return response()->json($ventas); 
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