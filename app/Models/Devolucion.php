<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devolucion extends Model
{
    use HasFactory;
    protected $table = 'devoluciones';

    protected $fillable = [
        'venta_id',
        'detalle_venta_id', // <-- CORRECCIÓN: Agregado para trazabilidad
        'producto_id',
        'cliente_id',
        // 'id_unico_producto', // <-- CORRECCIÓN: Eliminado de fillable
        'cantidad',
        'motivo',
        'costo_unitario',
        'notas',
        'estado_gestion',
    ];

    /**
     * Get the venta that owns the Devolucion.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Get the detalle de venta original.
     */
    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class, 'detalle_venta_id');
    }

    /**
     * Get the producto associated with the Devolucion.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Get the cliente associated with the Devolucion.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Get the saldoCliente records associated with the Devolucion (si aplica).
     */
    public function saldoCliente(): HasMany
    {
        return $this->hasMany(SaldoCliente::class);
    }

}