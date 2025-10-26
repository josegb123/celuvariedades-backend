<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    /** @use HasFactory<\Database\Factories\ClienteFactory> */
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';

    protected $fillable = [
        'cedula',
        'nombre',
        'apellidos',
        'telefono',
        'email',
        'direccion',
        'aval_id',
    ];

    /**
     * Get the Cartera associated with the Cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function cartera()
    {
        return $this->hasOne(Cartera::class);
    }

    /**
     * Get all of the Mensajes for the Cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mensajes()
    {
        return $this->hasMany(Mensaje::class);
    }
}
