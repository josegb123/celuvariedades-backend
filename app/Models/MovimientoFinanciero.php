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
        'tipo',
        'descripcion',
        'metodo_pago',
        'venta_id',
        'user_id',
        'referencia_tabla',
        'referencia_id',
        'caja_diaria_id',
    ];


    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimientoFinanciero::class, 'tipo_movimiento_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function caja()
    {
        return $this->belongsTo(CajaDiaria::class, 'caja_diaria_id');
    }
}