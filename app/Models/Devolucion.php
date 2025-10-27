<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Devolucion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venta_id',
        'user_id',
        'monto_devuelto',
        'razon',
        'estado',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación Cabecera-Detalle
    public function detalles()
    {
        return $this->hasMany(DetalleDevolucion::class);
    }
}
