<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequerimientoCalibracion extends Model
{
    use HasFactory;

    // 👈 Especificar el nombre exacto de la tabla
    protected $table = 'requerimiento_calibraciones';
    
    protected $fillable = ['nombre', 'descripcion'];
}