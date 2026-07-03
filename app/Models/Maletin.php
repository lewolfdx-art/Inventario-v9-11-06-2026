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
        'nombre',
        'responsable_solicitante',
        'observaciones',
        'estado',
    ];

    // ✅ RELACIÓN CON PRODUCTOS (muchos a muchos)
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'maletin_producto');
    }

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

    // ✅ Verificar si tiene productos asociados
    public function hasProductos(): bool
    {
        return $this->productos()->exists();
    }

    // ✅ Obtener lista de productos asociados
    public function getProductosListAttribute()
    {
        return $this->productos->pluck('nombre')->implode(', ');
    }
}