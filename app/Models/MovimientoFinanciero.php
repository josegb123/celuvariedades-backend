<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoFinanciero extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_movimiento_id',
        'monto',
        'tipo', // 🚨 Agregado
        'descripcion',
        'metodo_pago', // 🚨 Agregado
        'venta_id',
        'user_id',
        'referencia_tabla', // 🚨 Agregado
        'referencia_id', // 🚨 Agregado
    ];

    // 🚨 El campo 'tipo_movimiento_financieros' estaba mal escrito en la definición original

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimientoFinanciero::class, 'tipo_movimiento_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}