<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Producto extends Model
{
    use HasFactory;

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

    // ========== RECALIBRACIONES ==========
    
    public function getProximaRecalibracionAttribute()
    {
        return $this->recalibraciones()
            ->whereNotNull('proxima_recalibracion')
            ->orderBy('proxima_recalibracion', 'asc')
            ->first()
            ?->proxima_recalibracion;
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

    /**
     * ✅ Formato para mostrar en la tabla - CON DÍAS ENTEROS
     */
    public function getProximaRecalibracionFormattedAttribute()
    {
        $proxima = $this->proxima_recalibracion;
        if (!$proxima) return '📅 No programada';
        
        // ✅ CALCULAR DÍAS ENTEROS
        $hoy = now()->startOfDay();
        $fechaProxima = \Carbon\Carbon::parse($proxima)->startOfDay();
        $dias = (int) $hoy->diffInDays($fechaProxima, false);
        
        // Si la fecha es hoy
        if ($dias == 0) return '🔴 Hoy';
        
        // Si es en el futuro
        if ($dias > 0) {
            if ($dias == 1) return '🟠 Mañana';
            if ($dias <= 7) return '🟡 En ' . $dias . ' días';
            if ($dias <= 30) return '🟢 En ' . $dias . ' días';
            return '✅ En ' . $dias . ' días';
        }
        
        // Si es en el pasado (vencido)
        $diasVencido = abs($dias);
        if ($diasVencido == 1) return '⚠️ Vencido hace 1 día';
        return '⚠️ Vencido hace ' . $diasVencido . ' días';
    }

    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ' - ' . ($this->marca?->nombre ?? 'Sin marca') . ' - ' . $this->modelo;
    }
}