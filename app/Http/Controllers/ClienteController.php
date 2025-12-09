<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Requests\DeleteClienteRequest; // Added
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResource
    {
        // 1. Obtener el término de búsqueda de la solicitud.
        $search = $request->get('search');

        $query = Cliente::with('saldos');

        // 2. Aplicar la lógica de búsqueda solo si el parámetro 'search' existe.
        $query->where(function ($q) use ($search) {
            $q->where('nombre', 'LIKE', "%{$search}%")
                ->orWhere('cedula', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('telefono', 'LIKE', "%{$search}%");
        });

        // 3. Aplicar la paginación a la consulta final.
        $clientes = $query->paginate(15);

        return ClienteResource::collection($clientes);
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
        $query = Cliente::with('saldos')->find($cliente->id);

        // El Route Model Binding ya inyectó el cliente o lanzó 404
        return new ClienteResource($query);
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
    public function destroy(DeleteClienteRequest $request, Cliente $cliente) // Modified
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
