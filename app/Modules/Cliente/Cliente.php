<?php

namespace App\Modules\Cliente;

use App\Modules\Cartera\Cartera;
use App\Modules\Mensajeria\Mensaje;
use Database\Factories\ClienteFactory;
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
    public function Cartera()
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

    /**
     * Crea una nueva instancia de la factory para el modelo.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        // NOTA: Esta línea especifica la ruta real de tu ClienteFactory
        return ClienteFactory::new();
    }
}
