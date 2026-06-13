<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleGuiaRemision extends Model
{
    use HasFactory;

    protected $table = 'detalle_guia_remision';

    protected $fillable = [
        'guia_remision_id',
        'producto_id',
        'cantidad',
        'serie',
        'descripcion_completa',
    ];

    public function guiaRemision()
    {
        return $this->belongsTo(GuiaRemision::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    // Generar descripción completa automática: Nombre + Marca + Modelo + Serie
    public static function generarDescripcionCompleta($producto, $serie = null)
    {
        $descripcion = $producto->nombre;
        
        if ($producto->marca && $producto->marca->nombre) {
            $descripcion .= ' - ' . $producto->marca->nombre;
        }
        
        if ($producto->modelo) {
            $descripcion .= ' - ' . $producto->modelo;
        }
        
        if ($serie) {
            $descripcion .= ' - Serie: ' . $serie;
        }
        
        return $descripcion;
    }
}