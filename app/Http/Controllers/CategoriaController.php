<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/categorias
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        // Devuelve todas las categorías usando el CategoriaResource
        return CategoriaResource::collection(Categoria::all());
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/categorias
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // 1. Validar la solicitud
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255|unique:categorias,nombre',
            ]);

            // 2. Crear la categoría
            $categoria = Categoria::create($validatedData);

            // 3. Responder con el recurso recién creado y un código 201 (Created)
            return response()->json([
                'data' => new CategoriaResource($categoria),
                'message' => 'Categoría creada con éxito.'
            ], 201);

        } catch (ValidationException $e) {
            // Manejar errores de validación (código 422 Unprocessable Entity)
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/categorias/{categoria}
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Categoria  $categoria
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Categoria $categoria)
    {
        try {
            // 1. Validar la solicitud (Ignorando el nombre actual de la categoría)
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255|unique:categorias,nombre,' . $categoria->id,
            ]);

            // 2. Actualizar la categoría
            $categoria->update($validatedData);

            // 3. Responder con el recurso actualizado y un código 200 (OK)
            return response()->json([
                'data' => new CategoriaResource($categoria),
                'message' => 'Categoría actualizada con éxito.'
            ], 200);

        } catch (ValidationException $e) {
            // Manejar errores de validación
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/categorias/{categoria}
     *
     * @param  \App\Models\Categoria  $categoria
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Categoria $categoria)
    {
        // 1. Eliminar la categoría
        $categoria->delete();

        // 2. Responder con un código 204 (No Content) para indicar éxito sin cuerpo
        return response()->json(null, 204);
    }


}