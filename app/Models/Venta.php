<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'tipo_venta_id',
        'subtotal',
        'descuento_total',
        'iva_porcentaje',
        'iva_monto',
        'total',
        'estado',
        'metodo_pago',
        'fecha_emision',
    ];

    // Relaciones de pertenencia
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Relación Cabecera-Detalle (CRÍTICA)
    /**
     * Una venta tiene muchos detalles de venta (los productos vendidos).
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function tipoVenta(): BelongsTo
    {
        return $this->belongsTo(TipoVenta::class);
    }

    /**
     * Una Venta puede generar un único registro de Cartera (si es a crédito/separe).
     */
    public function cartera()
    {
        return $this->hasOne(Cartera::class);
    }

    /**
     * Define el Accessor para generar el resumen de productos.
     * Ejemplo: "3x Camisa Azul, 1x Pantalón Negro"
     * * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function resumenProductos(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Si la relación 'detalles' no está cargada, la cargamos para evitar N+1
                if (!$this->relationLoaded('detalles')) {
                    $this->load('detalles');
                }

                // Si aún no hay detalles (ej: factory en inicio), retornar vacío
                if ($this->detalles->isEmpty()) {
                    return 'Sin detalles de productos';
                }

                // Mapea la colección para crear el texto de cada ítem: "Cantidad x NombreProducto"
                $resumen = $this->detalles->map(function ($detalle) {
                    // Usamos el nombre histórico guardado en el detalle (más preciso)
                    return (float) $detalle->cantidad . 'x ' . $detalle->nombre_producto;
                });

                // Une todos los ítems en una sola cadena separada por coma
                return $resumen->implode(', ');
            }
        );
    }
}
