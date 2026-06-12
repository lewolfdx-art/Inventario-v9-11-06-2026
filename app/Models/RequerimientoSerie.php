<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequerimientoSerie extends Model
{
    use HasFactory;

    protected $table = 'requerimiento_series';
    
    protected $fillable = ['nombre', 'descripcion'];
}