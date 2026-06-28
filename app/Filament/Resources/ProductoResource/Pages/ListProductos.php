<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Producto;
use App\Models\Movimiento;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    public $contador_escaneos = 0;
    public $scanner_code = '';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Producto')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function updatedScannerCode($value): void
    {
        if (!empty($value) && strlen($value) > 3) {
            // ✅ LIMPIAR EL SKU: eliminar caracteres no deseados
            $sku = trim($value);
            
            // ✅ Eliminar prefijos como : :: > y otros caracteres extra
            $sku = ltrim($sku, ':>');
            $sku = trim($sku);
            
            // ✅ Reemplazar acentos y caracteres especiales
            $sku = str_replace(["'", "´", "`", '"', ';'], '-', $sku);
            
            // ✅ Eliminar espacios dobles y caracteres no permitidos
            $sku = preg_replace('/[^a-zA-Z0-9\-]/', '', $sku);
            
            $producto = Producto::where('sku', $sku)->first();
            
            if ($producto) {
                $this->ejecutarEscaneo($sku);
                $this->scanner_code = '';
            } else {
                // ✅ Intentar buscar sin el 0 si tiene
                if (str_starts_with($sku, '0')) {
                    $skuSinCero = substr($sku, 1);
                    $producto = Producto::where('sku', $skuSinCero)->first();
                    if ($producto) {
                        $this->ejecutarEscaneo($skuSinCero);
                        $this->scanner_code = '';
                        return;
                    }
                }
                
                Notification::make()
                    ->title('❌ Producto no encontrado')
                    ->body('No se encontró ningún producto con SKU: ' . $sku)
                    ->danger()
                    ->send();
                $this->scanner_code = '';
            }
        }
    }

    private function ejecutarEscaneo($sku): void
    {
        if (empty($sku)) {
            return;
        }

        $producto = Producto::where('sku', $sku)->first();

        if (!$producto) {
            Notification::make()
                ->title('❌ Producto no encontrado')
                ->body('No se encontró ningún producto con SKU: ' . $sku)
                ->danger()
                ->send();
            return;
        }

        $this->contador_escaneos++;

        if ($this->contador_escaneos % 2 == 1) {
            $tipo = 'salida';
            $icono = '📤';
            $color = 'warning';
            $mensaje = 'SALIDA';
        } else {
            $tipo = 'entrada';
            $icono = '📥';
            $color = 'success';
            $mensaje = 'ENTRADA';
        }

        $stockAnterior = $producto->stock ?? 0;

        if ($tipo === 'entrada') {
            $nuevoStock = $stockAnterior + 1;
        } else {
            if ($stockAnterior <= 0) {
                Notification::make()
                    ->title('❌ Sin stock disponible')
                    ->body('No hay stock para dar salida a ' . $producto->nombre)
                    ->danger()
                    ->send();
                return;
            }
            $nuevoStock = $stockAnterior - 1;
        }

        $producto->stock = $nuevoStock;
        $producto->save();

        Movimiento::create([
            'producto_id' => $producto->id,
            'tipo' => $tipo,
            'cantidad' => 1,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $nuevoStock,
        ]);

        Notification::make()
            ->title($icono . ' ' . $mensaje . ' #' . $this->contador_escaneos . ' registrada')
            ->body($producto->nombre . ' | Stock: ' . $stockAnterior . ' → ' . $nuevoStock)
            ->$color()
            ->send();

        $this->dispatch('refresh');
    }
}