<?php

namespace App\Http\Controllers;

use App\Models\CuentaPorCobrar;
use App\Http\Resources\CuentaPorCobrarResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuentaPorCobrarController extends Controller
{
    /**
     * Lista las cuentas por cobrar con soporte para filtros y paginación (resumen).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = CuentaPorCobrar::query()
            // Cargamos solo el cliente para el resumen de la tabla (N+1 prevention)
            ->with(['cliente'])
            ->orderBy('fecha_vencimiento', 'asc');

        // 1. FILTRO DE ESTADO
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        // 2. FILTRO POR FECHA DE VENCIMIENTO
        if ($request->filled('fecha_vencimiento')) {
            $query->whereDate('fecha_vencimiento', $request->input('fecha_vencimiento'));
        }

        // 3. FILTRO DE BÚSQUEDA POR CLIENTE (search)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('cliente', function (Builder $q) use ($searchTerm) {
                // Asumo que el cliente tiene campos 'nombre' y 'ruc_ci' o 'cedula'
                $q->where('nombre', 'like', "%{$searchTerm}%")
                    ->orWhere('ruc_ci', 'like', "%{$searchTerm}%");
            });
        }

        // Aplicar paginación (ej: 15 elementos por página)
        $cuentas = $query->paginate($request->input('per_page', 15));

        // *** INTEGRACIÓN DEL RESOURCE ***
        // Usamos collection para manejar la paginación y formatear cada item.
        return CuentaPorCobrarResource::collection($cuentas)->response();
        // **********************************
    }

    /**
     * Obtiene el detalle completo de una cuenta por cobrar, incluyendo relaciones.
     *
     * @param int $id
     * @return JsonResponse
     */
    /**
     * Obtiene el detalle completo de una cuenta por cobrar, incluyendo relaciones.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Usamos findOrFail para devolver un 404 si no se encuentra
        $cuenta = CuentaPorCobrar::with([
            'cliente', // El cliente asociado a la cuenta
            'venta.user', // La venta asociada y el usuario (vendedor) que la registró
            'venta.detalles', // Los productos/ítems de la venta
            'abonos.user', // El historial de abonos y el usuario que registró cada abono
        ])->findOrFail($id);

        // Puedes utilizar un Resource para formatear la respuesta si lo deseas, 
        // pero por simplicidad, devolvemos el objeto cargado.
        return response()->json($cuenta);
    }
}