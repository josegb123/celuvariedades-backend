<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse // ⬅️ Paso 1: Aceptar el objeto Request
    {
        // 1. Iniciar la consulta
        $query = Producto::with(['categoria', 'user']);

        // 2. ⬅️ Paso 2: Aplicar filtro si el parámetro 'search' está presente
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->input('search');

            // Aplicar la lógica de búsqueda (busca por nombre o código de barra)
            $q->where('nombre', 'like', '%' . $searchTerm . '%')
                ->orWhere('codigo_barra', 'like', '%' . $searchTerm . '%');
            // Opcional: Si los IDs son números grandes, puedes buscarlos también:
            // ->orWhere('id', $searchTerm);
        });

        // 3. Obtener los resultados paginados (limitamos a 10 productos por página para el buscador)
        $productos = $query->paginate(10);

        // 4. Devolver la respuesta
        return response()->json(ProductoResource::collection($productos));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductoRequest $request): JsonResponse
    {
        $producto = Producto::create($request->validated());
        return response()->json(new ProductoResource($producto->load(['categoria', 'user'])), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json(new ProductoResource($producto->load(['categoria', 'user'])));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductoRequest $request, Producto $producto): JsonResponse
    {
        $producto->update($request->validated());
        return response()->json(new ProductoResource($producto->load(['categoria', 'user'])));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto): JsonResponse
    {
        $producto->delete();
        return response()->json(null, 204);
    }
}
