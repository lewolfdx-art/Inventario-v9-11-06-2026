<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mencion extends Model
{
    use HasFactory;

    protected $table = 'mencions';

    protected $fillable = [
        'guia_requerimiento_id',
        'texto',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================
    public function guiaRequerimiento(): BelongsTo
    {
        return $this->belongsTo(GuiaRequerimiento::class, 'guia_requerimiento_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}