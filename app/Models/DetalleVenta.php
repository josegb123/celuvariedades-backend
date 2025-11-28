<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleVenta extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        // Relaciones
        'venta_id',
        'producto_id',

        // --- Campos Transaccionales ---
        'cantidad',
        'precio_unitario', // Precio Bruto por unidad (antes de descuento de línea)
        'subtotal',        // Cantidad * Precio Unitario (Neto de IVA, Bruto de descuento)

        // --- Campos Históricos (CRÍTICO) ---
        'nombre_producto', // Almacenar el nombre en el momento de la venta
        'codigo_barra',    // Almacenar el código en el momento de la venta
        'precio_costo',    // calcular la ganancia

        // --- Desglose de Impuestos y Descuentos ---
        'iva_porcentaje',  // Tasa de IVA (Ej: 19)
        'iva_monto',       // Monto de IVA
        'descuento_monto', // Monto de descuento aplicado a esta línea
        // Si tienes más impuestos (ej. INC) deberías agregarlos aquí
    ];

    /**
     * El detalle pertenece a una venta.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * El detalle corresponde a un producto.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}