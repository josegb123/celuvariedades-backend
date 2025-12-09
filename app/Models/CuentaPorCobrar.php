<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaPorCobrar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas_por_cobrar';

    protected $fillable = [
        'venta_id',
        'cliente_id',
        'monto_original',
        'monto_pendiente',
        'fecha_vencimiento',
        'estado', // Ej: Pendiente, Pagada, Vencida
    ];

    /**
     * Una Cuenta Por Cobrar pertenece a una Venta específica (1:1).
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * La cuenta pertenece a un Cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Una cuenta por cobrar tiene muchos abonos (transacciones de pago).
     */
    public function abonos(): HasMany
    {
        return $this->hasMany(AbonoCartera::class);
    }

    /**
     * Una cuenta por cobrar puede depositar su saldo en un saldo de cliente     
     */
    public function saldoCliente(): HasOne
    {
        return $this->HasOne(SaldoCliente::class);
    }

}