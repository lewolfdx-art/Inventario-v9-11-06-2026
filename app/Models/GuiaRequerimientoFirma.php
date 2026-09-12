<?php

namespace App\Models;

use App\Traits\SanitizesUtf8;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuiaRequerimientoFirma extends Model
{
    use HasFactory, SanitizesUtf8;

    protected $table = 'guia_requerimiento_firmas';

    protected $fillable = [
        'guia_requerimiento_id',
        'tipo',
        'firma_path',
        'fecha',
        'nombre_apellidos',
        'dni',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function guiaRequerimiento()
    {
        return $this->belongsTo(GuiaRequerimiento::class);
    }
}