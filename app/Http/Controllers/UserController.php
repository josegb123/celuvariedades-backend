<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Muestra una lista filtrada y paginada de usuarios.
     */
    public function index(Request $request)
    {
        // 1. Obtener parámetros de la URL: /api/users?search=admin&role=admin&page=1
        $search = $request->input('search');
        $role = $request->input('role');
        $perPage = $request->input('per_page', 10); // Valor por defecto 10

        $users = User::query()
            // 2. Aplicar filtro de búsqueda
            ->when($search, function ($query, $search) {
                // Buscar en nombre o email
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            // 3. Aplicar filtro de rol
            ->when($role, function ($query, $role) {
                $query->where('role', $role);
            })
            // 4. Aplicar ordenación (opcional)
            ->orderBy('name', 'asc')
            // 5. Paginar los resultados
            ->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     * Muestra un usuario específico *activo*.
     */
    public function show(User $user): JsonResponse
    {
        // El Route Model Binding de Laravel también excluye automáticamente 
        // los registros soft-deleted. Si el ID pertenece a un usuario eliminado,
        // devolverá 404.
        return (new UserResource($user))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            // El casting se encarga del hasheo
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return (new UserResource($user))->response();
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     * Elimina suavemente un usuario estableciendo la columna deleted_at.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        // ⚠️ Impedir que el usuario se elimine a sí mismo
        if (auth()->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta.'], 403);
        }

        // $user->delete() ahora realiza la eliminación suave.
        try {

            $user->delete();
            return response()->json(null, 204);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th], 500);

        }

    }

    /**
     * Restore the specified soft-deleted resource.
     * Restaura un usuario que ha sido eliminado suavemente (deleted_at = NULL).
     */
    public function restore(int $id): JsonResponse
    {
        // Usamos onlyTrashed() para buscar solo registros que han sido soft-deleted.
        // findOrFail($id) asegura que el registro existe y lanza 404 si no.
        $user = User::onlyTrashed()->findOrFail($id);

        // El método restore() establece deleted_at a NULL.
        if ($user->restore()) {
            // Devolvemos el usuario restaurado con código 200 OK.
            return (new UserResource($user))->response();
        }

        return response()->json(['message' => "No se pudo restaurar el usuario."], 500);
    }
}