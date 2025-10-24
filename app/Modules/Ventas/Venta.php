<?php

namespace App\Modules\Ventas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    /** @use HasFactory<\Database\Factories\VentaFactory> */
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'user_id',
        'fecha_emision',
        'descuento',
        'impuestos',
        'subtotal_venta',
        'total_venta'
    ];

    /**
     * Get the user associated with the Venta
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'foreign_key', 'local_key');
    }
}
