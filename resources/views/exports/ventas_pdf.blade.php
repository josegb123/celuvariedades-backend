<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Ventas por Periodo</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Ventas por Periodo</h1>
        <p>Generado el: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
        <p>Periodo de análisis: {{ $periodoAnalisis }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Periodo</th>
                <th>Ventas Totales</th>
                <th>Beneficio Bruto</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ventasData as $data)
                <tr>
                    <td>{{ $data['periodo_fecha'] }}</td>
                    <td>{{ number_format($data['ventas_totales'], 2) }}</td>
                    <td>{{ number_format($data['beneficio'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Página <span class="page-number"></span> de <span class="total-pages"></span>
    </div>
</body>
</html>
