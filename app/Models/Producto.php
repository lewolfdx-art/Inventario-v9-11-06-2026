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
 * @property int|null $año_fabricacion
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
 * @property string|null $observacion
 * @property \Illuminate\Support\Carbon|null $fecha_ultima_calibracion
 * @property \Illuminate\Support\Carbon|null $fecha_ultimo_mantenimiento
 * @property string $ubicacion
 * @property string|null $frecuencia_mantenimiento
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
        'año_fabricacion',
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
        'observacion',
        'fecha_ultima_calibracion',
        'fecha_ultimo_mantenimiento',
        'ubicacion',
        'frecuencia_mantenimiento',
    ];

    protected $casts = [
        'fecha_ultima_calibracion' => 'date',
        'fecha_ultimo_mantenimiento' => 'date',
    ];

    // ========== AUTO-SINCRONIZAR UNIDADES CON EL STOCK ==========
    // Al guardar un producto, las unidades físicas (barcodes) se ajustan solas:
    //  - Stock sube  → se crean las unidades que faltan (0SKU-NNN) como "disponibles"
    //  - Stock baja   → las últimas unidades disponibles pasan a "salido" (para poder escanearse de vuelta)
    protected static function booted()
    {
        static::saved(function (self $producto) {
            $producto->sincronizarUnidades();
        });
    }

    public function sincronizarUnidades()
    {
        $stock = max(0, (int) $this->stock);
        $disponibles = $this->unidades()->where('estado', 'disponible')->count();

        if ($stock === $disponibles) {
            return;
        }

        if ($stock > $disponibles) {
            // Faltan unidades disponibles → crearlas
            if (empty($this->sku)) {
                return;
            }

            $faltantes = $stock - $disponibles;
            $contador = $this->unidades()->count();

            for ($i = 0; $i < $faltantes; $i++) {
                $contador++;
                $codigo = '0' . $this->sku . '-' . str_pad((string) $contador, 3, '0', STR_PAD_LEFT);

                Unidad::firstOrCreate(['codigo' => $codigo], [
                    'producto_id' => $this->id,
                    'estado'      => 'disponible',
                ]);
            }
        } else {
            // Sobran unidades disponibles → marcarlas como salidas (se regresan escaneándolas)
            $exceso = $disponibles - $stock;

            $this->unidades()
                ->where('estado', 'disponible')
                ->orderByDesc('codigo')
                ->limit($exceso)
                ->update(['estado' => 'salido']);
        }
    }

    // ========== CONFIGURACIÓN DE LOGS ==========
    
    protected static $logAttributes = [
        'sku',
        'modelo',
        'nombre',
        'serie',
        'año_fabricacion',
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
        'observacion',
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
            ->useLogName('producto')
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

    // ========== ACCESORS PARA AÑO DE FABRICACIÓN ==========
    
    public function getAñoFabricacionFormattedAttribute()
    {
        return $this->año_fabricacion ? (string) $this->año_fabricacion : 'No especificado';
    }

    public static function getAñosFabricacionDisponibles()
    {
        return self::whereNotNull('año_fabricacion')
            ->select('año_fabricacion')
            ->distinct()
            ->orderBy('año_fabricacion', 'desc')
            ->pluck('año_fabricacion')
            ->toArray();
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

    // ✅ UNIDADES FÍSICAS (cada una con su código de barras propio)
    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'producto_id');
    }

    public function hasUnidades(): bool
    {
        return $this->unidades()->exists();
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

    // ========== SCOPES PARA FILTROS ==========
    
    /**
     * Excluye los productos huérfanos creados desde un maletín: aquellos de
     * categoría "Maleta" / subcategoría "Maletín" que ya no están vinculados a
     * ningún maletín (por ejemplo, porque el maletín fue eliminado).
     */
    public function scopeSinMaletinesHuerfanos($query)
    {
        $subId = Subcategoria::where('nombre', 'Maletín')->value('id');
        $catId = Categoria::where('nombre', 'Maleta')->value('id');

        if (! $subId && ! $catId) {
            return $query;
        }

        return $query->where(function ($q) use ($subId, $catId) {
            $q->where(function ($w) use ($subId, $catId) {
                if ($catId) {
                    $w->whereNull('categoria_id')->orWhere('categoria_id', '!=', $catId);
                }
                if ($subId) {
                    $w->orWhereNull('subcategoria_id')->orWhere('subcategoria_id', '!=', $subId);
                }
            })->orWhereHas('maletines');
        });
    }

    /**
     * Scope para filtrar por maletín
     */
    public function scopeEnMaletin($query, $maletinId)
    {
        if ($maletinId) {
            return $query->whereHas('maletines', function ($q) use ($maletinId) {
                $q->where('maletines.id', $maletinId);
            });
        }
        return $query;
    }

    /**
     * Scope para filtrar por año de fabricación
     */
    public function scopeAñoFabricacion($query, $año)
    {
        if ($año) {
            return $query->where('año_fabricacion', $año);
        }
        return $query;
    }

    /**
     * Scope para filtrar por rango de años
     */
    public function scopeRangoAños($query, $desde, $hasta)
    {
        if ($desde && $hasta) {
            return $query->whereBetween('año_fabricacion', [$desde, $hasta]);
        }
        return $query;
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

    // ========== CALIBRACIÓN / MANTENIMIENTO ==========

    public function getFechaBaseMantenimientoAttribute()
    {
        return collect([$this->fecha_ultima_calibracion, $this->fecha_ultimo_mantenimiento])
            ->filter()
            ->max();
    }

    public function getProximoMantenimientoAttribute()
    {
        $base = $this->fecha_base_mantenimiento;
        if (!$base) {
            return null;
        }

        return match ($this->frecuencia_mantenimiento) {
            '1_anio'  => $base->copy()->addYear(),
            '2_anios' => $base->copy()->addYears(2),
            default   => null,
        };
    }

    public function getVigenciaMantenimientoAttribute(): string
    {
        if ($this->frecuencia_mantenimiento === 'vida_util_unica') {
            return 'no_aplica';
        }
        if (!$this->frecuencia_mantenimiento) {
            return 'sin_definir';
        }

        $proximo = $this->proximo_mantenimiento;
        if (!$proximo) {
            return 'sin_fecha';
        }

        return $proximo->greaterThanOrEqualTo(now()->startOfDay()) ? 'vigente' : 'vencido';
    }

    // ========== ACTIVITY LOG RELATION ==========
    
    public function activities()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}