<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePedidoProveedorRequest;
use App\Http\Resources\PedidoProveedorResource;
use App\Models\PedidoProveedor;
use App\Services\PedidoProveedorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PedidoProveedorController extends Controller
{
    protected $pedidoProveedorService;

    public function __construct(PedidoProveedorService $pedidoProveedorService)
    {
        $this->pedidoProveedorService = $pedidoProveedorService;
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/pedidos-proveedor
     */
    public function store(StorePedidoProveedorRequest $request): JsonResponse
    {
        try {
            $pedido = $this->pedidoProveedorService->createPedidoProveedor($request->validated());

            return response()->json([
                'message' => 'Pedido a proveedor creado exitosamente.',
                'pedido' => $pedido->load('detalles.producto', 'proveedor', 'user') // Eager load relationships for response
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear el pedido a proveedor: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Error al crear el pedido a proveedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $query = PedidoProveedor::with(['detalles', 'user', 'proveedor']);

        // Filtro por término de búsqueda (nombre o código de barra)
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->input('search');
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('nombre', 'like', "%{$searchTerm}%")
                    ->orWhere('codigo_barra', 'like', "%{$searchTerm}%");
            });
        });
        $perPage = $request->input('per_page', 10);

        $pedido = $query->paginate($perPage);

        $pedido = $query->paginate(10);

        return response()->json(PedidoProveedorResource::collection($pedido)->response()->getData(true));
    }

    public function show(int $id): JsonResponse
    {
        $pedido = PedidoProveedor::findOrFail($id);
        if (!$pedido) {
            return response()->json(null, 404);
        }

        $pedido->load('detalles', 'proveedor', 'user');
        return response()->json($pedido);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/pedidos-proveedor/{pedidoProveedor}
     */
    public function update(UpdatePedidoProveedorRequest $request, PedidoProveedor $pedidoProveedor): JsonResponse
    {
        try {
            $pedido = $this->pedidoProveedorService->updatePedidoProveedor($request->validated(), $pedidoProveedor);

            return response()->json([
                'message' => 'Pedido a proveedor actualizado exitosamente.',
                'pedido' => $pedido->load('detalles.producto', 'proveedor', 'user')
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar el pedido a proveedor: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Error al actualizar el pedido a proveedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
