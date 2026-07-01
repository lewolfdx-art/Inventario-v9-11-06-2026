<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'observaciones',
        'realizado_por',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function imagenes()
    {
        return $this->hasMany(MovimientoFoto::class);
    }

    public function fotos()
    {
        return $this->hasMany(MovimientoFoto::class);
    }

    // ✅ CORREGIDO - Usa created_at en lugar de fecha_registro
    public function getUltimaImagenSalidaAttribute()
    {
        return $this->imagenes()
            ->where('tipo', 'salida')
            ->latest('created_at')
            ->first();
    }

    public function getUltimaImagenDevolucionAttribute()
    {
        return $this->imagenes()
            ->where('tipo', 'devolucion')
            ->latest('created_at')
            ->first();
    }

    public function hasFotos(): bool
    {
        return $this->imagenes()->exists();
    }
}