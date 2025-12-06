<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devolucion extends Model
{
    use HasFactory;
    protected $table = 'devoluciones';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cliente_id',
        'id_unico_producto',
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
}