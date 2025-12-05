<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoProveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pedido_proveedores';

    protected $fillable = [
        'numero_factura_proveedor',
        'fecha_entrega',
        'user_id',
        'proveedor_id',
        'monto_total',
        'estado',
    ];

    protected $casts = [
        'fecha_entrega' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetallePedidoProveedor::class);
    }
}
