<?php
// app/Models/Producto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

/**
 * @property int $id
 * @property string $sku
 * @property string $modelo
 * @property string $nombre
 * @property string|null $serie
 * @property int $stock
 * @property string|null $imagen
 * @property int $unidad_compra_id
 * @property int $naturaleza_id
 * @property int $req_inventario_id
 * @property int $req_serie_id
 * @property int $req_lote_id
 * @property int $req_calibracion_id
 * @property int $estado_id
 * @property int $categoria_id
 * @property int $subcategoria_id
 * @property int $marca_id
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Producto extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'sku',
        'modelo',
        'nombre',
        'serie',
        'stock',
        'imagen',
        'unidad_compra_id',
        'naturaleza_id',
        'req_inventario_id',
        'req_serie_id',
        'req_lote_id',
        'req_calibracion_id',
        'estado_id',
        'categoria_id',
        'subcategoria_id',
        'marca_id',
        'descripcion',
    ];

    // ========== CONFIGURACIÓN DE LOGS ==========
    
    protected static $logAttributes = [
        'sku',
        'modelo',
        'nombre',
        'serie',
        'stock',
        'imagen',
        'categoria_id',
        'subcategoria_id',
        'marca_id',
        'unidad_compra_id',
        'naturaleza_id',
        'req_inventario_id',
        'req_serie_id',
        'req_lote_id',
        'req_calibracion_id',
        'estado_id',
        'descripcion',
    ];

    protected static $logOnlyDirty = true;
    protected static $logFillable = true;
    protected static $logName = 'producto';
    protected static $ignoreChangedAttributes = ['updated_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Producto {$eventName}");
    }

    // ========== ACCESORS PARA IMAGEN ==========
    
    public function getImagenUrlAttribute()
    {
        if ($this->imagen) {
            if (Storage::disk('public')->exists($this->imagen)) {
                return asset('storage/' . $this->imagen);
            }
        }
        return asset('img/producto-default.png');
    }

    public function getHasImagenAttribute()
    {
        return !empty($this->imagen) && Storage::disk('public')->exists($this->imagen);
    }

    // ========== RELACIONES ==========
    
    public function unidadCompra()
    {
        return $this->belongsTo(UnidadCompra::class);
    }

    public function naturaleza()
    {
        return $this->belongsTo(Naturaleza::class);
    }

    public function reqInventario()
    {
        return $this->belongsTo(RequerimientoInventario::class, 'req_inventario_id');
    }

    public function reqSerie()
    {
        return $this->belongsTo(RequerimientoSerie::class, 'req_serie_id');
    }

    public function reqLote()
    {
        return $this->belongsTo(RequerimientoLote::class, 'req_lote_id');
    }

    public function reqCalibracion()
    {
        return $this->belongsTo(RequerimientoCalibracion::class, 'req_calibracion_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function recalibraciones()
    {
        return $this->hasMany(Recalibracion::class);
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }

    // ✅ RELACIÓN CON MALETINES (muchos a muchos)
    public function maletines()
    {
        return $this->belongsToMany(Maletin::class, 'maletin_producto');
    }

    // ✅ Verificar si tiene maletines asociados
    public function hasMaletines(): bool
    {
        return $this->maletines()->exists();
    }

    // ========== RECALIBRACIONES ==========
    
    public function getProximaRecalibracionAttribute()
    {
        $recalibracion = $this->recalibraciones()
            ->whereNotNull('proxima_recalibracion')
            ->orderBy('proxima_recalibracion', 'asc')
            ->first();
            
        return $recalibracion?->proxima_recalibracion;
    }

    public function getProximaRecalibracionColorAttribute()
    {
        $proxima = $this->proxima_recalibracion;
        if (!$proxima) return 'gray';
        
        $dias = (int) now()->startOfDay()->diffInDays($proxima, false);
        
        if ($dias < 0) return 'danger';
        if ($dias == 0) return 'danger';
        if ($dias <= 7) return 'warning';
        if ($dias <= 30) return 'info';
        return 'success';
    }

    public function getProximaRecalibracionFormattedAttribute()
    {
        $proxima = $this->proxima_recalibracion;
        if (!$proxima) return '📅 No programada';
        
        $hoy = now()->startOfDay();
        $fechaProxima = \Carbon\Carbon::parse($proxima)->startOfDay();
        $dias = (int) $hoy->diffInDays($fechaProxima, false);
        
        if ($dias == 0) return '🔴 Hoy';
        if ($dias > 0) {
            if ($dias == 1) return '🟠 Mañana';
            if ($dias <= 7) return '🟡 En ' . $dias . ' días';
            if ($dias <= 30) return '🟢 En ' . $dias . ' días';
            return '✅ En ' . $dias . ' días';
        }
        
        $diasVencido = abs($dias);
        if ($diasVencido == 1) return '⚠️ Vencido hace 1 día';
        return '⚠️ Vencido hace ' . $diasVencido . ' días';
    }

    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ' - ' . ($this->marca?->nombre ?? 'Sin marca') . ' - ' . $this->modelo;
    }

    // ========== ACTIVITY LOG RELATION ==========
    
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}