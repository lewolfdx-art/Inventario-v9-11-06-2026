<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $producto_id
 * @property string $codigo
 * @property string|null $serie
 * @property string $estado
 * @property string|null $ubicacion
 */
class Unidad extends Model
{
    use HasFactory;

    protected $table = 'producto_unidades';

    protected $fillable = [
        'producto_id',
        'codigo',
        'serie',
        'estado',
        'ubicacion',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'unidad_id');
    }

    public function getEtiquetaUnidadAttribute()
    {
        return 'Unidad ' . $this->numero;
    }

    public function getNumeroAttribute()
    {
        preg_match('/-(\d+)$/', $this->codigo, $matches);
        return str_pad($matches[1] ?? '', 3, '0', STR_PAD_LEFT);
    }
}