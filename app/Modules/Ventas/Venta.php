<?php

namespace App\Modules\Ventas;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    /** @use HasFactory<\Database\Factories\VentaFactory> */
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'user_id',
        'fecha_emision',
        'descuento',
        'impuestos',
        'subtotal_venta',
        'total_venta',
    ];

    /**
     * Get the user associated with the Venta
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
