<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\Unidad;
use Illuminate\Console\Command;

class GenerarUnidades extends Command
{
    protected $signature = 'unidades:generar';

    protected $description = 'Genera una unidad física (producto_unidades) por cada unidad de stock de cada producto. Idempotente: no duplica existentes.';

    public function handle(): int
    {
        $productos = Producto::where('stock', '>', 0)->whereNotNull('sku')->get();
        $total = 0;

        foreach ($productos as $producto) {
            $existentes = $producto->unidades()->count();

            if ($existentes >= $producto->stock) {
                continue;
            }

            for ($i = $existentes + 1; $i <= $producto->stock; $i++) {
                $codigo = '0' . $producto->sku . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);

                Unidad::firstOrCreate(['codigo' => $codigo], [
                    'producto_id' => $producto->id,
                    'estado' => 'disponible',
                ]);

                $total++;
            }

            $this->info("  {$producto->sku} ({$producto->nombre}): {$producto->stock} unidades");
        }

        $this->info("✅ Unidades generadas: {$total}");

        return self::SUCCESS;
    }
}