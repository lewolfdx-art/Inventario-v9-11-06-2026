<?php
// app/Models/MovimientoFoto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoFoto extends Model
{
    protected $fillable = [
        'movimiento_id',
        'ruta_imagen',
        'descripcion',
        'tipo',
    ];

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->ruta_imagen);
    }
}