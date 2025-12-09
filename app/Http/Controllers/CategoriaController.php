<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCategoriaRequest;
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
     * @param  \App\Http\Requests\StoreCategoriaRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCategoriaRequest $request)
    {
        // 1. Validar la solicitud (ya manejado por StoreCategoriaRequest)
        $validatedData = $request->validated();

        // 2. Crear la categoría
        $categoria = Categoria::create($validatedData);

        // 3. Responder con el recurso recién creado y un código 201 (Created)
        return response()->json([
            'data' => new CategoriaResource($categoria),
            'message' => 'Categoría creada con éxito.'
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/categorias/{categoria}
     *
     * @param  \App\Http\Requests\UpdateCategoriaRequest  $request
     * @param  \App\Models\Categoria  $categoria
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria)
    {
        // 1. Validar la solicitud (ya manejado por UpdateCategoriaRequest)
        $validatedData = $request->validated();

        // 2. Actualizar la categoría
        $categoria->update($validatedData);

        // 3. Responder con el recurso actualizado y un código 200 (OK)
        return response()->json([
            'data' => new CategoriaResource($categoria),
            'message' => 'Categoría actualizada con éxito.'
        ], 200);
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