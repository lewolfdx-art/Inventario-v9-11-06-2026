<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'modelo',
        'nombre',
        'serie',
        'stock',  // ✅ AGREGAR ESTO
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

    // Relaciones con todas las tablas
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

    // ========== RELACIÓN CON RECALIBRACIONES ==========
    public function recalibraciones()
    {
        return $this->hasMany(Recalibracion::class);
    }

    // Método para obtener la próxima recalibración más cercana
    public function getProximaRecalibracionAttribute()
    {
        return $this->recalibraciones()
            ->whereNotNull('proxima_recalibracion')
            ->orderBy('proxima_recalibracion', 'asc')
            ->first()
            ?->proxima_recalibracion;
    }

    // Color para el badge de próxima recalibración
    public function getProximaRecalibracionColorAttribute()
    {
        $proxima = $this->proxima_recalibracion;
        if (!$proxima) return 'gray';
        
        $dias = now()->diffInDays($proxima, false);
        
        if ($dias < 0) return 'danger';      // Vencido
        if ($dias <= 30) return 'warning';   // Por vencer (30 días)
        if ($dias <= 90) return 'info';      // Próximo (90 días)
        return 'success';                     // Ok
    }

    // Formato para mostrar en la tabla
    public function getProximaRecalibracionFormattedAttribute()
    {
        $proxima = $this->proxima_recalibracion;
        if (!$proxima) return '📅 No programada';
        
        $dias = now()->diffInDays($proxima, false);
        
        if ($dias < 0) return '⚠️ Vencido hace ' . abs($dias) . ' días';
        if ($dias == 0) return '🔴 Hoy';
        if ($dias == 1) return '🟠 Mañana';
        if ($dias <= 7) return '🟡 En ' . $dias . ' días';
        return '🟢 En ' . $dias . ' días';
    }

    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ' - ' . ($this->marca?->nombre ?? 'Sin marca') . ' - ' . $this->modelo;
    }

    // ========== RELACIÓN CON MOVIMIENTOS ==========
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class);
    }
}