<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMovimientoFinanciero extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function movimientosFinancieros()
    {
        return $this->hasMany(MovimientoFinanciero::class);
    }
}
