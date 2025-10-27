<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoVenta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'maneja_cartera',
        'reserva_stock',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
