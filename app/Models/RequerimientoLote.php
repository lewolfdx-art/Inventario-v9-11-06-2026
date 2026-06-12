<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequerimientoLote extends Model
{
    use HasFactory;

    protected $table = 'requerimiento_lotes';
    
    protected $fillable = ['nombre', 'descripcion'];
}