<?php

namespace App\Models;

use App\Traits\SanitizesUtf8;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaRequerimientoItem extends Model
{
    use HasFactory, SanitizesUtf8;

    protected $table = 'guia_requerimiento_items';

    protected $fillable = [
        'guia_requerimiento_id',
        'item',
        'descripcion',
        'cantidad_solicitada',
        'unidad_solicitada',
        'entregado',
        'cantidad_entregada',
        'unidad_entregada',
        'devuelto',
        'cantidad_devuelta',
        'unidad_devuelta',
        'orden',
    ];

    protected $casts = [
        'entregado'           => 'boolean',
        'devuelto'            => 'boolean',
        'cantidad_solicitada' => 'decimal:2',
        'cantidad_entregada'  => 'decimal:2',
        'cantidad_devuelta'   => 'decimal:2',
        'item'                => 'integer',
        'orden'               => 'integer',
    ];

    public function guiaRequerimiento()
    {
        return $this->belongsTo(GuiaRequerimiento::class);
    }
}