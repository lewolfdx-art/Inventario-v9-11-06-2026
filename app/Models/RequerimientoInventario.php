<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequerimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'requerimiento_inventarios';
    
    protected $fillable = ['nombre', 'descripcion'];
}