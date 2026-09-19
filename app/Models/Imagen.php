<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagens';

    protected $fillable = [
        'nombre',
        'archivo',
        'tipo',
        'mime_type',
        'tamaño',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ✅ Obtener URL pública de la imagen
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->archivo);
    }

    // ✅ Scope para imágenes activas
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    // ✅ Scope por tipo
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}