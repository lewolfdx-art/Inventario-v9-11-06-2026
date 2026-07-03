<?php
// app/Models/MaletinAccesorioSet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaletinAccesorioSet extends Model
{
    use HasFactory;

    protected $table = 'maletin_accesorios_set';

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