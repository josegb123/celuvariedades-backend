<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class EstadisticasController extends Controller
{
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
     * Asume que el modelo Venta tiene la relación 'cliente' y una columna 'total'.
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
     * Muestra las ventas totales agrupadas por día, mes o año.
     * Recibe el parámetro opcional 'periodo' ('day', 'month', 'year').
     */
    public function ventasPorPeriodo(Request $request): JsonResponse
    {
        // Por defecto, agrupa por mes
        $periodo = $request->get('periodo', 'month');

        // Define el formato de fecha para la función DATE_FORMAT de MySQL
        $dateFormat = match ($periodo) {
            'day' => '%Y-%m-%d',
            'year' => '%Y',
            default => '%Y-%m', // Month
        };

        $ventas = Venta::select(
            // Usa una función de DB para formatear la fecha y agrupar
            DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as periodo_fecha"),
            DB::raw('SUM(total) as ventas_totales')
        )
            // Puedes añadir un filtro de rango de fechas aquí (ej: últimas 6 meses)
            ->groupBy('periodo_fecha')
            ->orderBy('periodo_fecha')
            ->get();

        return response()->json([
            'periodo' => $periodo,
            'data' => $ventas,
        ]);
    }

    /**
     * Lista los productos cuyo stock está por debajo de un umbral (threshold).
     * Recibe el parámetro opcional 'umbral' (por defecto 5).
     * Asume que el modelo Producto tiene una columna 'stock'.
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
                ->where('estado', 'finalizada')
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
                ->where('estado', 'finalizada')
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

        $margenHistorico = DetalleVenta::query()
            // 1. Unir la tabla de detalle con la tabla de ventas
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')

            ->select(
                // 2. Usar el alias correcto: ventas.created_at
                DB::raw("DATE_FORMAT(ventas.created_at, '{$dateFormat}') as periodo_fecha"),
                // 3. Cálculo del margen (asumiendo que las columnas están en detalle_ventas)
                DB::raw('SUM((precio_unitario - precio_costo) * cantidad) as beneficio_bruto')
            )
            // Aplicar el filtro de rango de fechas (opcional)
            // ->when($request->has('fecha_inicio'), function ($query) use ($request) {
            //     $query->where('ventas.created_at', '>=', $request->fecha_inicio);
            // })

            ->groupBy('periodo_fecha')
            ->orderBy('periodo_fecha')
            ->get();

        return response()->json([
            'data' => $margenHistorico,
        ]);
    }
}