<?php

namespace App\Modules\Cartera;

use App\Modules\Cliente\Cliente;
use Database\Factories\CarteraFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cartera extends Model
{
    /** @use HasFactory<\Database\Factories\CarteraFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'saldo',
        'total_deuda',
    ];

    /**
     * Get the Cliente that owns the Cartera
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
        return CarteraFactory::new();
    }
}
