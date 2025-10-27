<?php

// app/Models/MovimientoInventario.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoInventario extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'producto_id',
        'user_id',
        'tipo_movimiento_id',
        'cantidad',
        'costo_unitario',
        'referencia_tabla',
        'referencia_id',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(TipoMovimientoInventario::class, 'tipo_movimiento_id');
    }
}
