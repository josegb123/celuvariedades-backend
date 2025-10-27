<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use App\Http\Requests\UpdateClienteRequest;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Retorna todos los clientes paginados (buena práctica para grandes datasets)
        return ClienteResource::collection(Cliente::paginate(15));
    }

    // --------------------------------------------------------------------------

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClienteRequest $request)
    {

        $cliente = Cliente::create($request->validated());

        return new ClienteResource($cliente);
    }

    // --------------------------------------------------------------------------

    /**
     * Display the specified resource.
     */
    public function show(Cliente $cliente)
    {
        // El Route Model Binding ya inyectó el cliente o lanzó 404
        return new ClienteResource($cliente);
    }

    // --------------------------------------------------------------------------

    /**
     * Update the specified resource in storage.
     *
     * @param  Cliente  $cliente  (Route Model Binding)
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        // 1. Validación de datos para la actualización
        // Se ignora el email/cédula actual del cliente para la verificación unique
        $validateData = $request->validated();

        // 2. Actualización del cliente
        $cliente->update($validateData);

        // 3. Retorna la versión actualizada del cliente (código 200 OK)
        return new ClienteResource($cliente);
    }

    // --------------------------------------------------------------------------

    /**
     * Remove the specified resource from storage.
     *
     * @param  Cliente  $cliente  (Route Model Binding)
     */
    public function destroy(Cliente $cliente)
    {
        // El Route Model Binding ya inyectó el cliente o lanzó 404

        // 1. Eliminación suave (Soft Delete) del cliente
        $cliente->delete();

        // 2. Retorna una respuesta vacía con código 204 (No Content)
        return response()->json(null, 204);
    }

    /**
     * Restore the specified soft-deleted resource.
     *
     * @param  int  $id  (Se usa el ID porque el Route Model Binding normal fallaría)
     */
    public function restore(int $id)
    {
        // 1. Usar withTrashed() para incluir registros eliminados suavemente
        // 2. Usar firstOrFail() para buscar por el ID y lanzar 404 si no existe (o no está eliminado)
        $cliente = Cliente::withTrashed()->where('id', $id)->firstOrFail();

        // Comprobar si el cliente realmente necesita ser restaurado
        if ($cliente->trashed()) {
            // 3. Usar el método restore() del modelo.
            $cliente->restore();
        }

        // 4. Retornar el recurso restaurado (código 200 OK)
        return new ClienteResource($cliente);
    }
}
