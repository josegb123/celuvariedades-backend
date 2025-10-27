<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'venta_id',
        'numero',
        'fecha_emision',
        'subtotal',
        'iva',
        'total',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
