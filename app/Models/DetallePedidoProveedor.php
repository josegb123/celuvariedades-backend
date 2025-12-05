<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePedidoProveedor extends Model
{
    use HasFactory;

    protected $table = 'detalle_pedido_proveedores';

    protected $fillable = [
        'pedido_proveedor_id',
        'producto_id',
        'cantidad',
        'precio_compra',
        'subtotal',
    ];

    public function pedidoProveedor()
    {
        return $this->belongsTo(PedidoProveedor::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
