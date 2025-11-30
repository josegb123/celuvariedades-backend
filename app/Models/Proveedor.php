<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Illuminate\Database\Eloquent\Relations\HasMany; // Ya no es necesario

class Proveedor extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo.
     * @var string
     */
    protected $table = 'proveedores';

    /**
     * Atributos que son asignables masivamente.
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre_comercial',
        'nombre_contacto',
        'identificacion',
        'telefono',
        'email',
        'direccion',
        'ciudad',
        'notas',
        'activo',
    ];

    /**
     * Atributos que deberían ser casteados a tipos nativos.
     * @var array
     */
    protected $casts = [
        'activo' => 'boolean',
    ];

    // --- RELACIONES ---

    /**
     * Un Proveedor puede suministrar muchos Productos (Muchos a Muchos).
     *
     * @return BelongsToMany
     */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'proveedor_producto', 'proveedor_id', 'producto_id')
            ->withPivot(['precio_costo', 'referencia_proveedor']) // Incluir campos de la tabla pivote
            ->withTimestamps(); // Si la tabla pivote tiene timestamps
    }

    // --- SCOPES ---

    /**
     * Scope para obtener solo proveedores activos.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}