<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\PedidoProveedor; // Added
use App\Models\Cliente; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel; // Added
use App\Exports\VentasExport; // Added

class EstadisticasController extends Controller
{
    /**
     * Exporta las ventas agrupadas a un archivo Excel.
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportarVentasExcel(Request $request)
    {
        $periodo = $request->input('periodo', 'month'); // Default to 'month'
        return Excel::download(new VentasExport($periodo), 'ventas_agrupadas_' . $periodo . '.xlsx');
    }

    /**
     * Identifica productos con pocas o ninguna venta en un período dado.
     * @param Request $request
     * @return JsonResponse
     */
    public function productosBajaRotacion(Request $request): JsonResponse
    {
        $periodDays = (int) $request->input('period_days', 90); // Default to 90 days
        $startDate = now()->subDays($periodDays);

        $ventasPorProducto = DetalleVenta::select('producto_id', DB::raw('SUM(cantidad) as total_vendido'))
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.created_at', '>=', $startDate)
            ->groupBy('producto_id')
            ->pluck('total_vendido', 'producto_id');

        $productosConBajaRotacion = Producto::whereNotIn('id', $ventasPorProducto->keys())
            ->orWhereIn('id', $ventasPorProducto->filter(fn($cantidad) => $cantidad <= 5)->keys()) // Products with 0-5 sales
            ->get(['id', 'nombre', 'stock_actual']);

        // Enhance with last sale date if available
        $resultados = $productosConBajaRotacion->map(function ($producto) use ($ventasPorProducto, $startDate) {
            $unidadesVendidas = $ventasPorProducto->get($producto->id, 0);

            $ultimaVenta = DetalleVenta::where('producto_id', $producto->id)
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->where('ventas.created_at', '>=', $startDate)
                ->latest('ventas.created_at')
                ->first();

            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'stock' => $producto->stock_actual,
                'unidades_vendidas_en_periodo' => (int) $unidadesVendidas,
                'ultima_venta' => $ultimaVenta ? $ultimaVenta->venta->created_at->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'periodo_dias' => $periodDays,
            'data' => $resultados,
        ]);
    }

    /**
     * Calcula el monto total gastado en pedidos a proveedores dentro de un rango de fechas.
     * @param Request $request
     * @return JsonResponse
     */
    public function valorPedidosProveedores(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'error' => 'Fechas de inicio y fin son requeridas (start_date, end_date).'
            ], 400);
        }

        $query = PedidoProveedor::query()
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalGastoProveedores = (float) $query->sum('total');

        $detallesPorProveedor = $query->select('proveedor_id', DB::raw('SUM(total) as total_gastado'))
            ->groupBy('proveedor_id')
            ->with([
                'proveedor' => function ($q) {
                    $q->select('id', 'nombre');
                }
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'proveedor_id' => $item->proveedor_id,
                    'nombre_proveedor' => $item->proveedor->nombre ?? 'Proveedor Desconocido',
                    'total_gastado' => (float) $item->total_gastado,
                ];
            });

        return response()->json([
            'total_gasto_proveedores' => $totalGastoProveedores,
            'periodo' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'detalles_por_proveedor' => $detallesPorProveedor,
        ]);
    }

    /**
     * Identifica los clientes que han realizado el mayor número de compras en un período determinado.
     * @param Request $request
     * @return JsonResponse
     */
    public function topClientesFrecuencia(Request $request): JsonResponse
    {
        $periodDays = (int) $request->input('period_days', 90); // Default to 90 days
        $limit = (int) $request->input('limit', 10); // Default to top 10 clients
        $startDate = now()->subDays($periodDays);

        $topClientes = Venta::select(
            'cliente_id',
            DB::raw('COUNT(id) as numero_compras_en_periodo'),
            DB::raw('MAX(created_at) as ultima_compra')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('cliente_id')
            ->orderByDesc('numero_compras_en_periodo')
            ->limit($limit)
            ->with([
                'cliente' => function ($q) {
                    $q->select('id', 'nombre', 'email');
                }
            ])
            ->get();

        $resultados = $topClientes->map(function ($item) {
            return [
                'cliente_id' => $item->cliente_id,
                'nombre_cliente' => $item->cliente->nombre ?? 'Cliente Desconocido',
                'email_cliente' => $item->cliente->email ?? null,
                'numero_compras_en_periodo' => (int) $item->numero_compras_en_periodo,
                'ultima_compra' => $item->ultima_compra ? $item->ultima_compra->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'periodo_dias' => $periodDays,
            'limit' => $limit,
            'data' => $resultados,
        ]);
    }
    /**
     * Calcula el Top 10 de productos más vendidos.
     */
    public function topProductosVendidos(): JsonResponse
    {
        // Usamos la tabla de detalle de ventas para agrupar y contar.
        $topProductos = DetalleVenta::select(
            'producto_id',
            // Alias 'total_vendido' para la suma de la columna 'cantidad'
            DB::raw('SUM(cantidad) as total_vendido')
        )
            ->groupBy('producto_id')
            ->orderByDesc('total_vendido')
            ->take(10) // Limitar a los 10 primeros
            ->with([
                'producto' => function ($query) {
                    // Seleccionamos solo el nombre del producto para eficiencia
                    $query->select('id', 'nombre');
                }
            ])
            ->get();

        // Formatear el resultado para que sea más fácil de consumir en el Front-end
        $resultados = $topProductos->map(function ($item) {
            return [
                'producto_id' => $item->producto_id,
                'nombre_producto' => $item->producto->nombre ?? 'Producto Eliminado', // Manejo de producto no encontrado
                'unidades_vendidas' => (int) $item->total_vendido, // Convertir a entero
            ];
        });

        return response()->json([
            'data' => $resultados,
        ]);
    }

    /**
     * Calcula el Top 10 de clientes por monto total de ventas.     
     */
    public function topClientes(): JsonResponse
    {
        $topClientes = Venta::select(
            'cliente_id',
            // Suma el total de las ventas para cada cliente
            DB::raw('SUM(total) as monto_total_comprado')
        )
            ->groupBy('cliente_id')
            ->orderByDesc('monto_total_comprado')
            ->take(10)
            // Carga la información del cliente para mostrar el nombre/email
            ->with([
                'cliente' => function ($query) {
                    $query->select('id', 'nombre', 'email');
                }
            ])
            ->get();

        $resultados = $topClientes->map(function ($item) {
            return [
                'cliente_id' => $item->cliente_id,
                'nombre_cliente' => $item->cliente->nombre ?? 'Cliente Desconocido',
                'monto_total' => (float) $item->monto_total_comprado,
            ];
        });

        return response()->json([
            'data' => $resultados,
        ]);
    }


    /**
     * Lista los productos cuyo stock está por debajo de un umbral (threshold).
     * Recibe el parámetro opcional 'umbral' (por defecto 5).     
     */
    public function productosBajoStock(Request $request): JsonResponse
    {
        // Obtener el umbral del request (ej: si es 5, buscará stock <= 5).
        $umbral = (int) $request->get('umbral', 5);

        $productos = Producto::select('id', 'nombre', 'stock_actual')
            ->where('stock_actual', '<=', $umbral)
            ->orderBy('stock_actual') // Mostrar el más bajo primero
            ->get();

        return response()->json([
            'umbral' => $umbral,
            'data' => $productos,
        ]);
    }

    public function getVentasPorPeriodo(Request $request): JsonResponse
    {
        // 1. Obtener y validar el periodo solicitado
        $periodo = $request->input('periodo', 'month'); // Valor por defecto: month

        $dateFormat = match ($periodo) {
            'day' => '%Y-%m-%d',     // Agrupa por día (ej: 2025-11-20)
            'year' => '%Y',          // Agrupa por año (ej: 2025)
            default => '%Y-%m',      // Agrupa por mes (ej: 2025-11)
        };

        try {
            // 2. Ejecutar la consulta de agregación
            $ventasPorPeriodo = Venta::query()
                ->where(function ($query) {
                    $query->where('ventas.estado', 'finalizada')
                        ->orWhere('ventas.estado', 'parcialmente devuelta');
                })
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as periodo_fecha"),
                    DB::raw('SUM(total) as ventas_totales')
                )
                ->groupBy('periodo_fecha')
                ->orderBy('periodo_fecha')
                ->get();

            // 3. Calcular el beneficio por periodo
            $beneficioPorPeriodo = DetalleVenta::query()
                ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
                ->where('ventas.estado', 'finalizada')
                ->select(
                    DB::raw("DATE_FORMAT(ventas.created_at, '{$dateFormat}') as periodo_fecha"),
                    DB::raw('SUM((precio_unitario - precio_costo) * cantidad) as beneficio')
                )
                ->groupBy('periodo_fecha')
                ->orderBy('periodo_fecha')
                ->get()
                ->keyBy('periodo_fecha');

            // 4. Unir ventas y beneficio por periodo
            $resultados = $ventasPorPeriodo->map(function ($item) use ($beneficioPorPeriodo) {
                $beneficio = $beneficioPorPeriodo[$item->periodo_fecha]->beneficio ?? 0;
                return [
                    'periodo_fecha' => $item->periodo_fecha,
                    'ventas_totales' => (float) $item->ventas_totales,
                    'beneficio' => (float) $beneficio,
                ];
            });

            // 5. Devolver la respuesta
            return response()->json([
                'periodo' => $periodo,
                'data' => $resultados,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo obtener las Ventas por Período.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTicketPromedio(): JsonResponse
    {
        try {
            // Calcula el promedio de la columna 'total' de la tabla 'ventas'
            $promedio = Venta::query()
                // Excluye ventas canceladas o reembolsadas (ajusta según tus estados)
                ->where(function ($query) {
                    $query->where('ventas.estado', 'finalizada')
                        ->orWhere('ventas.estado', 'parcialmente devuelta');
                })
                ->avg('total');

            // Si el resultado es null (no hay ventas), se devuelve 0
            $montoPromedio = round($promedio ?? 0, 2);

            return response()->json([
                'monto_promedio_venta' => $montoPromedio,
                'unidad' => 'COP' // O la unidad monetaria que uses
            ]);

        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json([
                'error' => 'No se pudo calcular el Ticket Promedio.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function historialGanancias(Request $request): JsonResponse
    {
        $periodo = $request->get('periodo', 'month');
        $dateFormat = match ($periodo) {
            'day' => '%Y-%m-%d',
            'year' => '%Y',
            default => '%Y-%m',
        };

        $query = DetalleVenta::query()
            // 1. Unir la tabla de detalle con la tabla de ventas
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')

            // 2. FILTRO CORREGIDO: Agrupamos las condiciones de estado
            ->where(function ($query) {
                $query->where('ventas.estado', 'finalizada')
                    ->orWhere('ventas.estado', 'parcialmente devuelta');
            })

            ->select(
                DB::raw("DATE_FORMAT(ventas.updated_at, '{$dateFormat}') as periodo_fecha"),

                // Calculamos el beneficio usando la cantidad neta restante (asumiendo que 'cantidad' fue actualizada)
                DB::raw('SUM((detalle_ventas.precio_unitario - detalle_ventas.precio_costo) * detalle_ventas.cantidad) as beneficio_bruto')
            )

            // 3. Aplicar el filtro de rango de fechas
            ->when($request->has(['fecha_inicio', 'fecha_fin']), function ($query) use ($request) {
                // Aseguramos que el filtro de fecha se aplique a ventas.updated_at
                $query->whereBetween('ventas.updated_at', [
                    $request->fecha_inicio . ' 00:00:00',
                    $request->fecha_fin . ' 23:59:59'
                ]);
            })

            ->groupBy('periodo_fecha')
            ->orderBy('periodo_fecha');

        $margenHistorico = $query->get(); // Ejecutamos la consulta

        return response()->json([
            'data' => $margenHistorico,
        ]);
    }
}