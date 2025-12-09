<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaldoCliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'saldos_clientes';

    protected $fillable = [
        'cliente_id',
        'cuenta_por_cobrar_id', // Referencia a la deuda que generó el saldo
        'monto_original', // Monto total que se le debe al cliente
        'monto_pendiente', // Saldo actual a favor del cliente
        'estado', // Activo, Usado, Expirado
        'motivo', // Anulación, Devolución, Reembolso        
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorCobrar::class);
    }

}