<?php

namespace App\Http\Controllers;

use App\Models\DetalleNegocio;
use App\Models\detalles_negocio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Storage;

class DetallesNegocioController extends Controller
{/**
 * Obtiene los detalles del negocio.
 */
    public function show(): JsonResponse
    {
        // Retorna el primer registro o valores vacíos por defecto
        $settings = DetalleNegocio::first() ?? new DetalleNegocio();

        return response()->json($settings);
    }

    /**
     * Actualiza o crea los detalles del negocio.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string|max:500',
            'admin' => 'nullable|string|max:255',
            'nit' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048|mimes:jpeg,png,jpg,gif,svg,webp',
        ]);

        $settings = DetalleNegocio::first() ?? new DetalleNegocio();

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->handleImageUpload($request, $settings->logo);
        } else {
            unset($validated['logo']);
        }

        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'message' => 'Configuración actualizada con éxito',
            'data' => $settings->fresh()
        ]);
    }

    private function handleImageUpload(Request $request, ?string $oldLogoUrl): string
    {
        // 1. Eliminar el logo anterior si existe
        if ($oldLogoUrl) {
            // Extraemos solo la ruta del archivo (ej: details/foto.jpg)
            // Eliminamos el dominio y los prefijos conocidos
            $search = ['/api/storage/', '/storage/', asset('')];
            $relativePath = str_replace($search, '', $oldLogoUrl);

            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }

        // 2. Almacenar nueva imagen (esto devuelve ej: "details/abc123.png")
        $path = $request->file('logo')->store('details', 'public');

        // 3. Construir la URL con el prefijo /api/ manualmente
        // asset('storage/' . $path) genera: http://tu-dominio.com/storage/details/abc123.png
        $fullUrl = asset('storage/' . $path);

        // Reemplazamos SOLO la primera ocurrencia de /storage por /api/storage
        // Usamos un replace más específico para no vaciar la cadena
        return str_replace('/storage', '/api/storage', $fullUrl);
    }

}
