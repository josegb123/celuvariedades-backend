<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cartera extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'carteras'; // Nombre de la tabla en la base de datos

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'monto_original',
        'monto_pendiente',
        'fecha_vencimiento',
        'estado',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
