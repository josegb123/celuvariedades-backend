<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbonoCartera extends Model
{
    use HasFactory;
    // No usamos SoftDeletes aquí, ya que los pagos suelen ser registros inmutables.

    protected $table = 'abono_carteras';

    protected $fillable = [
        'cuenta_por_cobrar_id',
        'user_id',
        'monto_abonado',
        'metodo_pago',
        'referencia_pago',
    ];

    /**
     * El abono pertenece a una Cuenta Por Cobrar.
     */
    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorCobrar::class);
    }

    /**
     * El abono fue registrado por un Usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}