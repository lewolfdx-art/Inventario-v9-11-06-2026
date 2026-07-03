<?php
// app/Models/MaletinAccesorioAdicional.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaletinAccesorioAdicional extends Model
{
    use HasFactory;

    protected $table = 'maletin_accesorios_adicionales';

    protected $fillable = [
        'maletin_id',
        'item_numero',
        'cantidad',
        'descripcion',
        'incluido',
    ];

    protected $casts = [
        'incluido' => 'boolean',
    ];

    public function maletin()
    {
        return $this->belongsTo(Maletin::class);
    }
}