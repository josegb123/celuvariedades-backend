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
        'descripcion',
        'fecha',
    ];

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimientoFinanciero::class);
    }
}
