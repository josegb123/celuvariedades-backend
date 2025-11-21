<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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
    // 1. Obtener los datos validados
    $data = $request->validated();
    
    // 2. Manejo de la subida de la imagen
    if ($request->hasFile('imagen')) {
        // Almacenar el archivo en el disco 'public' dentro de la carpeta 'productos'
        $path = $request->file('imagen')->store('productos', 'public');
        
        // Obtener la URL pública y guardarla en los datos para el modelo
        $data['imagen_url'] = Storage::url($path); 
    }
    
    // 3. Crear el producto en la base de datos
    $producto = Producto::create($data);
    
    // 4. Devolver la respuesta con el resource
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
    // 1. Obtener los datos validados
    $data = $request->validated();
    
    // 2. Manejo de la subida de la imagen
    if ($request->hasFile('imagen')) {
        
        // 2a. Opcional: Eliminar la imagen antigua si existe
        if ($producto->imagen_url) {
            // Convertir la URL pública a la ruta interna para poder eliminarla
            $oldPath = str_replace('/storage/', '', $producto->imagen_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        
        // 2b. Almacenar el nuevo archivo
        $path = $request->file('imagen')->store('productos', 'public');
        
        // 2c. Guardar la nueva URL
        $data['imagen_url'] = Storage::url($path);
    
    } elseif (isset($data['imagen_url']) && $data['imagen_url'] === null) {
        // 2d. Si la imagen se elimina explícitamente (se envía null en el request)
        if ($producto->imagen_url) {
            $oldPath = str_replace('/storage/', '', $producto->imagen_url);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        $data['imagen_url'] = null;
    }
    
    // 3. Actualizar el producto en la base de datos
    $producto->update($data);
    
    // 4. Devolver la respuesta
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
