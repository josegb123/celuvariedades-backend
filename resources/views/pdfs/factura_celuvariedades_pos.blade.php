<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Factura POS Celuvariedades #{{ $venta->numero_factura ?? $venta->id }}</title>
  <style>
    /* CSS para simular el recibo de 80mm */
    body {
      font-family: 'Arial', sans-serif;
      margin: 0;
      padding: 0;
      background-color: #fff width: 80mm;
      font-size: 8px;
      /* ⚠️ REDUCIDO A 8PX PARA MÁS ESPACIO */
      color: #000;
    }

    .container {
      padding: 0px;
      /* Pequeño padding interno si es necesario */
    }

    .header {
      text-align: center;
      margin-bottom: 10px;
    }

    .header h3 {
      margin: 0;
      font-size: 14px;
    }

    .header p {
      margin: 1px 0;
    }

    .divider {
      border-top: 1px dashed #000;
      margin: 5px 0;
    }

    .details {
      margin-bottom: 5px;
      text-align: left;
    }

    .details p {
      margin: 1px 0;
      line-height: 1.3;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
      /* ⚠️ REDUCIDO A 8PX */
    }

    th,
    td {
      padding: 1px 0;
      /* ⚠️ REDUCIDO EL PADDING */
      text-align: left;
      vertical-align: top;
      /* Aseguramos que la descripción pueda romper línea */
      word-wrap: break-word;
    }

    .center {
      text-align: center;
    }

    .right {
      text-align: right;
    }

    .total-box {
      margin-top: 10px;
    }

    .total-box tr td {
      font-size: 10px;
      padding: 3px 0;
    }

    .total-box .total {
      font-weight: bold;
      font-size: 12px;
      border-top: 1px dashed #000;
    }
  </style>
</head>

<body>
  <div class="container">

    <div class="header">
      <h3>CELUVARIEDADES M y K</h3>
      <p>KAREN MILENA MURILLO MARTÍNEZ</p>
      <p>NIT: 1.073.980.539 - 6</p>
      <p style="font-style: italic;">Venta al por menor de celulares, accesorios, recargas y minutos</p>
      <p>Calle 5 No. 13-08 Corregimiento de Villa Fátima Buenavista - Córdoba</p>
      <p>Tel: 316 654 26 37</p>
    </div>

    <div class="divider"></div>

    <div class="details">
      <p><strong>FACTURA DE VENTA N°:</strong> {{ $venta->id }}</p>
      <p><strong>FECHA:</strong> {{ $venta->created_at->format('d/m/Y H:i') }}</p>
      <p><strong>SEÑOR(ES):</strong> {{ $venta->cliente->nombre ?? 'N/A' }}</p>
      <p><strong>C.C. / NIT:</strong> {{ $venta->cliente->cedula ?? 'N/A' }}</p>
      <p><strong>TIPO VENTA:</strong> {{ strtoupper($venta->metodo_pago ?? 'CONTADO') }}</p>
      <p><strong>VENDEDOR:</strong> {{ $venta->user->name ?? 'N/A' }}</p>
    </div>

    <div class="divider"></div>

    <table>
      <thead>
        <tr>
          <th class="center" style="width: 10%;">CANT. </th>
          <th style="width: 55%;">DESCRIPCION</th>
          <th class="right" style="width: 15%;">VR. UNIT.</th>
          <th class="right" style="width: 20%;">VALOR TOTAL</th>
        </tr>
      </thead>
      <tbody>
        {{-- Iterar sobre los detalles de la venta --}}
        @foreach($venta->detalles as $detalle)
          <tr>
            <td class="center">{{ number_format($detalle->cantidad, 0) }}</td>
            <td style="word-wrap: break-word;">{{ $detalle->nombre_producto }}</td>
            <td class="right">$ {{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
            <td class="right">$ {{ number_format($detalle->subtotal, 0, ',', '.') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="divider"></div>

    <table class="total-box">
      <tr>
        <td style="width: 70%;">SUBTOTAL $</td>
        <td class="right">$ {{ number_format($venta->subtotal ?? 0, 0, ',', '.') }}</td>
      </tr>
      <tr>
        <td>IVA $</td>
        <td class="right">$ {{ number_format($venta->iva_monto ?? 0, 0, ',', '.') }}</td>
      </tr>
      <tr class="total">
        <td>TOTAL $</td>
        <td class="right">$ {{ number_format($venta->total ?? 0, 0, ',', '.') }}</td>
      </tr>
    </table>

    <div class="divider"></div>

    <div class="footer center">
      <p>ACEPTADA CLIENTE: ___________________</p>
      <p>C.C./NIT: ___________________</p>
      <p>VENDEDOR: ___________________</p>
      <p>¡Gracias por su compra!</p>
    </div>
  </div>
</body>

</html>