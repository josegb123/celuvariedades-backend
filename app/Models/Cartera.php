<?php

namespace App\Models;

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
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
