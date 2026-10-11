<?php
// app/Models/MaletinComponente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaletinComponente extends Model
{
    use HasFactory;

    protected $table = 'maletin_componentes';

    protected $fillable = [
        'maletin_id',
        'item_numero',
        'cantidad',
        'descripcion',
        'incluido',
        'estado_devolucion',
    ];

    protected $casts = [
        'incluido' => 'boolean',
    ];

    public function maletin()
    {
        return $this->belongsTo(Maletin::class);
    }
}