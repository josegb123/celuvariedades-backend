<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecibirPedidoRequest;
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
     */
    public function store(RecibirPedidoRequest $request): JsonResponse
    {
        try {
            $pedido = $this->pedidoProveedorService->receiveOrder($request->validated());

            return response()->json([
                'message' => 'Pedido de proveedor recibido exitosamente.',
                'pedido' => $pedido->load('detalles.producto', 'proveedor', 'user') // Eager load relationships for response
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al procesar el pedido de proveedor: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'message' => 'Error al procesar el pedido de proveedor.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
