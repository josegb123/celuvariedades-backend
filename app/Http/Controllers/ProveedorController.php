<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Http\Resources\ProveedorResource;
use App\Http\Resources\ProveedorCollection;
use Illuminate\Http\JsonResponse;
use Exception;

class ProveedorController extends Controller
{
    /**
     * Muestra una lista paginada y/o filtrada de los proveedores.
     * Utiliza ProveedorCollection para estandarizar la respuesta paginada.
     *
     * @param Request $request
     * @return ProveedorCollection
     */
    public function index(Request $request): ProveedorCollection
    {
        $query = Proveedor::query();

        // Filtro por estado activo (opcional)
        if ($request->has('activo')) {
            // Convierte a booleano de forma segura
            $query->where('activo', filter_var($request->activo, FILTER_VALIDATE_BOOLEAN));
        }

        // Búsqueda por nombre o identificación
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre_comercial', 'like', "%{$search}%")
                    ->orWhere('identificacion', 'like', "%{$search}%");
            });
        }

        // Paginación por defecto 10
        $proveedores = $query->paginate($request->get('per_page', 10));

        // Retorna la colección de recursos
        return new ProveedorCollection($proveedores);
    }

    /**
     * Almacena un nuevo proveedor utilizando el Form Request.
     *
     * @param StoreProveedorRequest $request
     * @return ProveedorResource|JsonResponse
     */
    public function store(StoreProveedorRequest $request): ProveedorResource|JsonResponse
    {
        try {
            $proveedor = Proveedor::create($request->validated());

            // Retorna el recurso individual formateado
            return (new ProveedorResource($proveedor))
                ->response()
                ->setStatusCode(201); // 201 Created

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al crear el proveedor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra un proveedor específico.
     *
     * @param Proveedor $proveedor
     * @return ProveedorResource
     */
    public function show(Proveedor $proveedor): ProveedorResource
    {
        // Retorna el recurso individual formateado
        return new ProveedorResource($proveedor);
    }

    /**
     * Actualiza un proveedor existente utilizando el Form Request.
     *
     * @param UpdateProveedorRequest $request
     * @param Proveedor $proveedor
     * @return ProveedorResource|JsonResponse
     */
    public function update(UpdateProveedorRequest $request, Proveedor $proveedor): ProveedorResource|JsonResponse
    {
        try {
            $proveedor->update($request->validated());

            // Retorna el recurso individual formateado
            return new ProveedorResource($proveedor);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el proveedor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un proveedor.
     *
     * @param Proveedor $proveedor
     * @return JsonResponse
     */
    public function destroy(Proveedor $proveedor): JsonResponse
    {
        try {
            // Lógica de seguridad: Recomendado si hay relaciones importantes
            if ($proveedor->productos()->exists()) {
                return response()->json("No se puede eliminar, el proveedor tiene productos registrados", 409);
            }

            $proveedor->delete();

            return response()->json([
                'message' => 'Proveedor eliminado con éxito.'
            ], 204); // 204 No Content

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el proveedor',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}