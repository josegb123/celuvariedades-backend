<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleNegocio extends Model
{
    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'admin',
        'nit',
        'logo'
    ];

}
