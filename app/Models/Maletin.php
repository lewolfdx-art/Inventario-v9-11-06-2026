<?php
// app/Models/Maletin.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maletin extends Model
{
    use HasFactory;

    protected $table = 'maletins';

    protected $fillable = [
        'nombre',  // ✅ NUEVO CAMPO
        'responsable_solicitante',
        'observaciones',
        'estado',
    ];

    public function componentesEquipo()
    {
        return $this->hasMany(MaletinComponente::class)->orderBy('item_numero');
    }

    public function accesoriosSet()
    {
        return $this->hasMany(MaletinAccesorioSet::class)->orderBy('item_numero');
    }

    public function accesoriosAdicionales()
    {
        return $this->hasMany(MaletinAccesorioAdicional::class)->orderBy('item_numero');
    }
}