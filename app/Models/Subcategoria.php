<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'categoria_id',
        'descripcion',
    ];

    // Relación inversa con Categoria
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }
}