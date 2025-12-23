<?php

use App\Http\Controllers\VentaController;
use App\Models\DetalleNegocio;
use App\Models\Venta;
use Illuminate\Support\Facades\Route;

Route::get('/factura/imprimir/{venta}', [VentaController::class, 'imprimirFacturaPos']);

Route::get('/debug-factura/{id?}', function ($id = null) {
  // 1. Intentamos buscar la venta solicitada o la última registrada
  $venta = $id
    ? Venta::with(['detalles', 'cliente', 'user'])->find($id)
    : Venta::with(['detalles', 'cliente', 'user'])->latest()->first();

  // 2. Si no hay ventas en la base de datos, lanzamos un error manual para avisar
  if (!$venta) {
    return "Error: No se encontró ninguna venta en la base de datos para previsualizar.";
  }

  // 3. Obtenemos el registro del negocio
  $negocio = DetalleNegocio::first();

  // 4. Retornamos la VISTA DIRECTA (sin pasar por DomPDF)
  // Cambia 'pdfs.factura_celuvariedades_pos' por la ruta real de tu archivo .blade.php
  return view('pdfs.factura_celuvariedades_pos', compact('venta', 'negocio'));
})->name('factura.debug');