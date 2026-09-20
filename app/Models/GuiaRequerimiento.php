<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    // ==========================================
    // RELACIONES
    // ==========================================

    public function items(): HasMany
    {
        return $this->hasMany(GuiaRequerimientoItem::class)
                    ->orderBy('orden')
                    ->orderBy('item');
    }

    public function firmas(): HasMany
    {
        return $this->hasMany(GuiaRequerimientoFirma::class);
    }

    // ✅ NUEVA RELACIÓN: Firmas de ENTREGA (3 tipos)
    public function firmasEntrega(): HasMany
    {
        return $this->hasMany(GuiaRequerimientoFirma::class, 'guia_requerimiento_id')
                    ->whereIn('tipo', ['atendido_por', 'autorizado_por', 'recibi_conforme']);
    }

    // ✅ NUEVA RELACIÓN: Firma de DEVOLUCIÓN (1 tipo)
    public function firmaDevolucion(): HasMany
    {
        return $this->hasMany(GuiaRequerimientoFirma::class, 'guia_requerimiento_id')
                    ->where('tipo', 'recepcion_devolucion');
    }

    // ✅ MENCIONES (SIN orden)
    public function menciones(): HasMany
    {
        return $this->hasMany(Mencion::class, 'guia_requerimiento_id');
    }

    // ✅ Solo menciones activas (SIN orden)
    public function mencionesActivas(): HasMany
    {
        return $this->hasMany(Mencion::class, 'guia_requerimiento_id')
                    ->where('activo', true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SANITIZACIÓN UTF-8
    // ==========================================

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