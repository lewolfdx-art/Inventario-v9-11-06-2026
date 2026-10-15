<?php

// app/Models/Maletin.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Maletin extends Model
{
    use HasFactory;

    protected $table = 'maletins';

    protected $fillable = [
        'nombre',
        'responsable_solicitante',
        'proyecto_cliente',
        'observaciones',
        'estado',
        'fecha_solicitud',
        'fecha_devolucion',
        'fecha_atencion',
        'revisado_por',
        'version',
        'aprobado_por',
        'fecha_documento',
        'entregado_por_nombres',
        'entregado_por_dni',
        'entregado_por_firma',
        'recibido_por_nombres',
        'recibido_por_dni',
        'recibido_por_firma',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_devolucion' => 'date',
        'fecha_atencion' => 'date',
    ];

    // Al eliminar un maletín, se eliminan los productos que fueron creados desde él
    // (categoría "Maleta" / subcategoría "Maletín") y que ya no pertenecen a ningún
    // otro maletín, para que no queden productos huérfanos en el inventario.
    protected static function booted()
    {
        static::deleting(function (self $maletin) {
            foreach ($maletin->productos as $producto) {
                // Desvincular del maletín que se está eliminando.
                $producto->maletines()->detach($maletin->getKey());

                // Si otro maletín todavía lo usa, no se toca.
                if ($producto->maletines()->exists()) {
                    continue;
                }

                // Solo se eliminan los productos autogenerados desde un maletín.
                $esProductoDeMaletin = $producto->subcategoria?->nombre === 'Maletín'
                    || $producto->categoria?->nombre === 'Maleta';

                if (! $esProductoDeMaletin) {
                    continue;
                }

                // No se elimina si está referenciado en una guía (FK restrictiva).
                $enUso = (Schema::hasTable('guias_remision')
                        && DB::table('guias_remision')->where('producto_id', $producto->getKey())->exists())
                    || (Schema::hasTable('detalle_guia_remision')
                        && DB::table('detalle_guia_remision')->where('producto_id', $producto->getKey())->exists());

                if ($enUso) {
                    continue;
                }

                $producto->delete();
            }
        });
    }

    // ✅ RELACIÓN CON PRODUCTOS (muchos a muchos)
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'maletin_producto');
    }

    public function componentesEquipo()
    {
        return $this->hasMany(MaletinComponente::class)->orderBy('item_numero');
    }

    public function accesoriosSet()
    {
        return $this->hasMany(MaletinAccesorioSet::class)->orderBy('item_numero');
    }

    public function accesoriosAdicionales()
    {
        return $this->hasMany(MaletinAccesorioAdicional::class)->orderBy('item_numero');
    }

    // ✅ Verificar si tiene productos asociados
    public function hasProductos(): bool
    {
        return $this->productos()->exists();
    }

    // ✅ Obtener lista de productos asociados
    public function getProductosListAttribute()
    {
        return $this->productos->pluck('nombre')->implode(', ');
    }
}
