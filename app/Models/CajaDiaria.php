<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para controlar la apertura y cierre de la caja diaria o por turno.
 * Representa la sesión de caja activa que ancla todas las transacciones de efectivo.
 */
class CajaDiaria extends Model
{
    use HasFactory;

    protected $table = 'cajas_diarias';

    protected $fillable = [
        'user_id',
        'fecha_apertura',
        'fondo_inicial',
        'fecha_cierre',
        'monto_cierre_teorico',
        'monto_cierre_fisico',
        'diferencia',
        'estado',
    ];

    protected $casts = [
        'fondo_inicial' => 'float',
        'monto_cierre_teorico' => 'float',
        'monto_cierre_fisico' => 'float',
        'diferencia' => 'float',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    /**
     * Relación con el usuario (cajero) que abrió la caja.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con todas las ventas registradas durante esta sesión de caja.
     */
    public function ventas(): HasMany
    {
        // Se asume la existencia de la columna 'caja_diaria_id' en la tabla 'ventas'
        return $this->hasMany(Venta::class, 'caja_diaria_id');
    }
}