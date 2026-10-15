<?php

namespace App\Console\Commands;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Subcategoria;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LimpiarMaletinesHuerfanos extends Command
{
    protected $signature = 'productos:limpiar-maletines-huerfanos {--dry-run : Solo muestra lo que se borraría, sin eliminar nada}';

    protected $description = 'Elimina los productos creados desde un maletín (categoría "Maleta" / subcategoría "Maletín") que ya no están vinculados a ningún maletín.';

    public function handle(): int
    {
        $catId = Categoria::where('nombre', 'Maleta')->value('id');
        $subId = Subcategoria::where('nombre', 'Maletín')->value('id');

        if (! $catId && ! $subId) {
            $this->warn('No se encontraron la categoría "Maleta" ni la subcategoría "Maletín". Nada por limpiar.');

            return self::SUCCESS;
        }

        $huerfanos = Producto::query()
            ->where(function ($w) use ($catId, $subId) {
                if ($catId) {
                    $w->orWhere('categoria_id', $catId);
                }
                if ($subId) {
                    $w->orWhere('subcategoria_id', $subId);
                }
            })
            ->whereDoesntHave('maletines')
            ->get();

        if ($huerfanos->isEmpty()) {
            $this->info('✅ No hay productos de maletín huérfanos.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $borrados = 0;
        $omitidos = 0;

        foreach ($huerfanos as $producto) {
            $enUso = (Schema::hasTable('guias_remision')
                    && DB::table('guias_remision')->where('producto_id', $producto->id)->exists())
                || (Schema::hasTable('detalle_guia_remision')
                    && DB::table('detalle_guia_remision')->where('producto_id', $producto->id)->exists());

            if ($enUso) {
                $omitidos++;
                $this->line("  ⏭️  Omitido (en guía): {$producto->sku} - {$producto->nombre}");

                continue;
            }

            $this->line('  ' . ($dryRun ? '🔍' : '🗑️') . " {$producto->sku} - {$producto->nombre}");

            if (! $dryRun) {
                $producto->delete();
            }

            $borrados++;
        }

        $accion = $dryRun ? 'se borrarían' : 'eliminados';
        $this->info("✅ {$borrados} producto(s) {$accion}. {$omitidos} omitido(s) por estar en guías.");

        return self::SUCCESS;
    }
}
