<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('nombre', 'like', "%{$searchTerm}%")
                    ->orWhere('codigo_barra', 'like', "%{$searchTerm}%");
            });
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

        // 1. Prioridad: Archivo subido
        if ($request->hasFile('imagen')) {
            $data = $this->handleImageUpload($request, $data);

            // 2. Si no hay archivo, usar la URL provista si existe
        } elseif (isset($data['imagen_url']) && $data['imagen_url'] !== 'null') {
            // La validación ya aseguró que es una URL válida
            // No hacemos nada, el valor de $data['imagen_url'] se guardará directamente.

            // 3. Si no hay archivo ni URL, o se envió 'null', asegurar que la URL sea null
        } else {
            $data['imagen_url'] = null;
        }

        // Aseguramos que 'imagen' no pase al modelo si no fue manejado (aunque $request->validated() debería hacerlo)
        unset($data['imagen']);

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
        $mustDeleteOldImage = false;

        // 1. Prioridad: Archivo subido
        if ($request->hasFile('imagen')) {
            $mustDeleteOldImage = true; // Se subirá una nueva, la anterior debe irse.
            $data = $this->handleImageUpload($request, $data);

            // 2. Si no hay archivo, verificar la URL del input (v-model="form.imagen_input_url" en el frontend)
        } elseif (isset($data['imagen_url'])) {

            if ($data['imagen_url'] === 'null') {
                // El usuario pidió explícitamente borrar la imagen existente.
                $mustDeleteOldImage = true;
                $data['imagen_url'] = null; // Guardar NULL en la base de datos.
            }
            // Si $data['imagen_url'] contiene una URL válida, se guarda directamente.

        } else {
            // Esto ocurre si no se tocó el campo de imagen en el frontend (ni file, ni URL).
            // Se mantiene el valor existente en $producto->imagen_url.
            // Para asegurar que el 'update' no lo borre si no está presente en $data,
            // no incluimos 'imagen_url' en $data si no se modificó.
            unset($data['imagen_url']);
        }

        // Lógica de eliminación: si se subió un nuevo archivo O se pidió borrar la imagen actual.
        if ($mustDeleteOldImage && $producto->imagen_url) {
            $this->handleImageDeletion($producto);
        }

        // Aseguramos que 'imagen' no pase al modelo
        unset($data['imagen']);

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
    private function handleImageUpload(Request $request, array $data): array
    {
        // 1. Almacenar nueva imagen
        // Usamos 'productos' como subdirectorio en el disco 'public'
        $path = $request->file('imagen')->store('productos', 'public');

        // 2. Generar la URL completa (usando Storage::url() que es el método canónico de Laravel)
        // asset() es más seguro para el puerto, pero Storage::url() es el estándar.
        // Asumiendo que APP_URL está correctamente configurado en .env y config/filesystems.php
        $data['imagen_url'] = Storage::url($path);

        // 3. Eliminar el objeto UploadedFile
        unset($data['imagen']);

        return $data;
    }

    /**
     * Elimina el archivo de imagen del disco si el URL apunta a un archivo local.
     * @param Producto $producto
     */
    private function handleImageDeletion(Producto $producto): void
    {
        if (!$producto->imagen_url) {
            return;
        }

        // Comprobar si la URL es una URL de Storage de Laravel (contiene /storage/)
        // y si el host/path concuerda con la configuración local (Storage::url() o asset()).
        // Si no es una URL local, NO la eliminamos.

        $storagePrefix = 'storage/';
        $urlPath = parse_url($producto->imagen_url, PHP_URL_PATH);

        // 1. Verificar si el path contiene el prefijo de storage
        if (str_contains($urlPath, $storagePrefix)) {
            // 2. Limpiar el prefijo /storage/ para obtener el path relativo al disco
            $oldPath = trim(substr($urlPath, strpos($urlPath, $storagePrefix) + strlen($storagePrefix)), '/');

            // 3. Eliminar del disco 'public'
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Si la imagen_url fue una URL externa (ej. https://otra-web.com/img.jpg), 
        // no pasa la comprobación y no intentamos borrarla.
    }
}