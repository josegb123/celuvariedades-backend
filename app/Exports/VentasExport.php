<?php

namespace App\Exports;

use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;

class VentasExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $periodo;

    public function __construct(string $periodo = 'month')
    {
        $this->periodo = $periodo;
    }

    /**
    * @return Collection
    */
    public function collection(): Collection
    {
        $dateFormat = match ($this->periodo) {
            'day' => '%Y-%m-%d',
            'year' => '%Y',
            default => '%Y-%m', // month
        };

        // 1. Obtener ventas totales por periodo
        $ventasPorPeriodo = Venta::query()
            ->where('estado', 'finalizada')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as periodo_fecha"),
                DB::raw('SUM(total) as ventas_totales')
            )
            ->groupBy('periodo_fecha')
            ->orderBy('periodo_fecha')
            ->get()
            ->keyBy('periodo_fecha');

        // 2. Obtener beneficio por periodo
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

        // 3. Combinar los resultados
        $data = $ventasPorPeriodo->map(function ($item) use ($beneficioPorPeriodo) {
            $beneficio = $beneficioPorPeriodo[$item->periodo_fecha]->beneficio ?? 0;
            return [
                'periodo_fecha' => $item->periodo_fecha,
                'ventas_totales' => (float) $item->ventas_totales,
                'beneficio' => (float) $beneficio,
            ];
        });

        return new Collection($data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Periodo',
            'Ventas Totales',
            'Beneficio Bruto',
        ];
    }
}
