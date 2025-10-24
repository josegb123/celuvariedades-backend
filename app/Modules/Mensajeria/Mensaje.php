<?php

namespace App\Modules\Mensajeria;

use App\Modules\Cliente\Cliente;
use Database\Factories\MensajeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mensaje extends Model
{
    /** @use HasFactory<\Database\Factories\MensajeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['mensaje', 'cliente_id'];

    /**
     * Get the Cliente that owns the Mensaje
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Crea una nueva instancia de la factory para el modelo.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        // NOTA: Esta línea especifica la ruta real de tu ClienteFactory
        return MensajeFactory::new();
    }
}
