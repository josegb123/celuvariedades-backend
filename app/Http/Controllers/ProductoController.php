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
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with(['categoria', 'user']);

        // Filtro por término de búsqueda (nombre o código de barra)
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->input('search');

            $q->where('nombre', 'like', "%{$searchTerm}%")
                ->orWhere('codigo_barra', 'like', "%{$searchTerm}%");
        });

        // Filtro por ID de categoría
        $query->when($request->filled('categoria_id'), function ($q) use ($request) {
            $q->where('categoria_id', $request->input('categoria_id'));
        });

        $productos = $query->paginate(10);

        return response()->json(ProductoResource::collection($productos));
    }

    // -------------------------------------------------------------------

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductoRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('imagen')) {
            $data = $this->handleImageUpload($request, $data);
        }

        $producto = Producto::create($data);

        return response()->json(new ProductoResource($producto->load(['categoria', 'user'])), 201);
    }

    // -------------------------------------------------------------------

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json(new ProductoResource($producto->load(['categoria', 'user'])));
    }

    // -------------------------------------------------------------------

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductoRequest $request, Producto $producto): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('imagen')) {
            // Eliminar la imagen anterior y subir la nueva
            $data = $this->handleImageUpload($request, $data, $producto);
        } elseif (isset($data['imagen_url']) && $data['imagen_url'] === null) {
            // Eliminar imagen si se pide explícitamente y no se sube una nueva
            $this->handleImageDeletion($producto);
            $data['imagen_url'] = null;
        }

        $producto->update($data);

        return response()->json(new ProductoResource($producto->load(['categoria', 'user'])));
    }

    // -------------------------------------------------------------------

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto): JsonResponse
    {
        // Se recomienda eliminar la imagen asociada al producto antes de eliminar el registro
        if ($producto->imagen_url) {
            $this->handleImageDeletion($producto);
        }

        $producto->delete();
        return response()->json(null, 204);
    }

    // -------------------------------------------------------------------
    // MÉTODOS PRIVADOS PARA MANEJO DE ARCHIVOS
    // -------------------------------------------------------------------

    /**
     * Sube el nuevo archivo y genera la URL.
     * @param Request $request
     * @param array $data
     * @param Producto|null $producto
     * @return array
     */
    private function handleImageUpload(Request $request, array $data, ?Producto $producto = null): array
    {
        // 1. Eliminar antigua imagen si existe (solo en update)
        if ($producto && $producto->imagen_url) {
            $this->handleImageDeletion($producto);
        }

        // 2. Almacenar nueva imagen
        // Usamos 'productos' como subdirectorio en el disco 'public'
        $path = $request->file('imagen')->store('productos', 'public');

        // 3. Generar la URL completa (usando asset() o Storage::url() con la configuración APP_URL correcta)
        // Usamos asset() ya que fue el método que resolvió el problema del puerto en desarrollo
        $data['imagen_url'] = asset('storage/' . $path);

        // 4. Eliminar el objeto UploadedFile
        unset($data['imagen']);

        return $data;
    }

    /**
     * Elimina el archivo de imagen del disco.
     * @param Producto $producto
     */
    private function handleImageDeletion(Producto $producto): void
    {
        if (!$producto->imagen_url) {
            return;
        }

        // Extraer el path relativo (e.g., productos/archivo.jpg) del URL
        $urlPath = parse_url($producto->imagen_url, PHP_URL_PATH);

        // Limpiar el prefijo /storage/ para obtener el path relativo al disco
        // Esto funciona incluso si la URL tiene host/puerto/storage/...
        $oldPath = trim(str_replace('/storage/', '', $urlPath), '/');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}