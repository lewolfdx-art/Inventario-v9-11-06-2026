<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaRemision extends Model
{
    use HasFactory;

    protected $table = 'guias_remision';

    protected $fillable = [
        'numero_guia',
        'producto_id',
        'marca',
        'modelo',
        'serie',
        'fecha_emision',
        'descripcion_completa',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public static function generarNumeroGuia()
    {
        $ultimo = self::orderBy('id', 'desc')->first();
        $numero = $ultimo ? intval(substr($ultimo->numero_guia, -4)) + 1 : 1;
        return 'GR-' . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public static function generarDescripcionCompleta($producto)
    {
        $descripcion = $producto->nombre ?? '';
        
        if ($producto->marca) {
            $descripcion .= ' - ' . $producto->marca->nombre;
        }
        
        if ($producto->modelo) {
            $descripcion .= ' - ' . $producto->modelo;
        }
        
        if ($producto->serie) {
            $descripcion .= ' - Serie: ' . $producto->serie;
        }
        
        return $descripcion;
    }
}