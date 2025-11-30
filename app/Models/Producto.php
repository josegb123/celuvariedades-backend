<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'categoria_id',
        'user_id',
        'codigo_barra',
        'nombre',
        'descripcion',
        'imagen_url',
        'precio_compra',
        'precio_venta',
        'stock_actual',
        'stock_reservado',
        'stock_minimo',
    ];

    /**
     * El producto pertenece a una categoría.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * El producto fue registrado por un usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Un producto puede estar en muchos detalles de venta (relación con DetalleVenta).
     */
    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    /**
     * Un Producto puede ser suministrado por muchos Proveedores (Relación Muchos a Muchos).
     * Esta relación utiliza la tabla pivote 'proveedor_producto'.
     *
     * @return BelongsToMany
     */
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'proveedor_producto', 'producto_id', 'proveedor_id')
            // Incluimos los campos extra de la tabla pivote
            ->withPivot(['precio_costo', 'referencia_proveedor'])
            ->withTimestamps(); // Si tu tabla pivote tiene created_at/updated_at
    }
}
