<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuiaRequerimiento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guia_requerimientos';

    protected $fillable = [
        'codigo',
        'version',
        'revisado_por',
        'aprobado_por',
        'fecha_documento',
        'pagina',
        'total_paginas',
        'nombre_proyecto',
        'responsable_solicitante',
        'fecha_pedido',
        'centro_costos',
        'fecha_atencion',
        'comentarios',
        'estado',
        'user_id',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'fecha_pedido'    => 'date',
        'fecha_atencion'  => 'date',
        'pagina'          => 'integer',
        'total_paginas'   => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(GuiaRequerimientoItem::class)
                    ->orderBy('orden')
                    ->orderBy('item');
    }

    public function firmas()
    {
        return $this->hasMany(GuiaRequerimientoFirma::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Sanitización UTF-8 al hidratar y guardar
    protected static function booted(): void
    {
        static::retrieved(fn ($model) => self::sanitizeAttributes($model));
        static::saving(fn ($model) => self::sanitizeAttributes($model));
    }

    protected static function sanitizeAttributes($model): void
    {
        foreach ($model->getAttributes() as $key => $value) {
            if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                $model->setAttribute($key, mb_convert_encoding($value, 'UTF-8', 'UTF-8'));
            }
        }
    }
}