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
        $query = Producto::with(['categoria', 'user', 'proveedores']);

        // 1. Filtros básicos
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->input('search');
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('nombre', 'like', "%{$searchTerm}%")
                    ->orWhere('codigo_barra', 'like', "%{$searchTerm}%");
            });
        });

        $query->when($request->filled('categoria_id'), function ($q) use ($request) {
            $q->where('categoria_id', $request->input('categoria_id'));
        });

        // 2. Lógica POS: Usando Subquery para evitar errores de GROUP BY
        if ($request->has('pos')) {
            $query->addSelect([
                'total_vendido' => \App\Models\DetalleVenta::selectRaw('COALESCE(SUM(cantidad), 0)')
                    ->whereColumn('producto_id', 'productos.id')
            ])->orderByDesc('total_vendido');
        } else {
            $query->latest();
        }

        // 3. Paginación
        $perPage = $request->input('per_page', 18);
        $productos = $query->paginate($perPage);

        return response()->json(ProductoResource::collection($productos)->response()->getData(true));
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

        // 3. Guardar la Relación Pivot (Muchos a Muchos)
        if (!empty($request->has('proveedores'))) {

            // adjunta los IDs proporcionados.
            foreach ($request->input('proveedores') as $proveedorId) {
                $producto->proveedores()->attach($proveedorId);
            }

        }

        return response()->json(new ProductoResource($producto->load(['categoria', 'user', 'proveedores'])), 201);
    }

    // -------------------------------------------------------------------

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json(new ProductoResource($producto->load(['categoria', 'user', 'proveedores'])));
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
        // 1. Almacenar nueva imagen en la carpeta 'productos'
        // Retorna algo como: "productos/nombre_archivo.jpg"
        $path = $request->file('imagen')->store('productos', 'public');

        // 2. Generar la URL con el prefijo /api/storage
        // asset('storage/' . $path) genera: http://tu-dominio/storage/productos/abc.jpg
        $fullUrl = asset('storage/' . $path);

        // Inyectamos el /api antes de /storage
        $data['imagen_url'] = str_replace('/storage', '/api/storage', $fullUrl);

        // 3. Limpiar el objeto del array para evitar errores al guardar en DB
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

        // El prefijo que estamos inyectando en las URLs
        $apiStoragePrefix = '/api/storage/';

        // 1. Obtener el path de la URL (ej: /api/storage/productos/img.jpg)
        $urlPath = parse_url($producto->imagen_url, PHP_URL_PATH);

        // 2. Verificar si la imagen es gestionada localmente por nuestra API
        if (str_contains($urlPath, $apiStoragePrefix)) {

            // Extraemos lo que hay DESPUÉS de /api/storage/
            // Si la URL es /api/storage/productos/foto.png -> obtenemos productos/foto.png
            $relativePath = explode($apiStoragePrefix, $urlPath)[1] ?? null;

            // 3. Eliminar del disco 'public' si el path es válido
            if ($relativePath && Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }

    /**
     * Muestra una lista paginada de productos que están en bajo stock.
     * La lógica es: stock_actual <= stock_minimo.
     */
    public function getBajoStock(Request $request): JsonResponse
    {
        // 1. Inicializar la consulta con las relaciones necesarias
        $query = Producto::with(['categoria', 'user', 'proveedores']);

        // 2. Aplicar la condición de bajo stock
        // Utilizamos whereRaw para filtrar donde la columna 'stock_actual' es menor o igual a 'stock_minimo'
        $query->whereRaw('stock_actual <= stock_minimo');

        // 3. Opcional: Filtro adicional por término de búsqueda (nombre/código)
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->input('search');
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('nombre', 'like', "%{$searchTerm}%")
                    ->orWhere('codigo_barra', 'like', "%{$searchTerm}%");
            });
        });

        // 4. Aplicar paginación
        $perPage = $request->input('per_page', 99);
        $productos = $query->paginate($perPage);

        if ($productos->isEmpty()) {
            return response()->json([
                'message' => 'No hay productos en bajo stock.',
                'data' => [],
            ]);
        }
        // 5. Devolver la respuesta usando el Resource. El Resource se encargará de añadir el Accessor
        // 'is_bajo_stock' a cada producto en la respuesta.
        return response()->json(ProductoResource::collection($productos)->response()->getData(true));
    }
}