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
     * Get all of the Saldos for the Cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function saldos()
    {
        return $this->hasMany(SaldoCliente::class);
    }


    /**
     * Get all of the ventas for the Cliente
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Un Cliente tiene muchas Cuentas Por Cobrar activas o históricas.
     */
    public function cuentasPorCobrar()
    {
        return $this->hasMany(CuentaPorCobrar::class, 'cliente_id');
    }
}
