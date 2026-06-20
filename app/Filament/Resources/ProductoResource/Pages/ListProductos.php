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
    public $scanner_code = ''; // ✅ Campo exclusivo para el escáner

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Producto')
                ->icon('heroicon-o-plus'),
        ];
    }

    // ✅ Captura el escaneo desde el campo "scanner_code"
    public function updatedScannerCode($value): void
    {
        if (!empty($value) && strlen($value) > 3) {
            $sku = str_replace(["'", "´", "`"], '-', $value);
            
            $producto = Producto::where('sku', $sku)->first();
            
            if ($producto) {
                $this->ejecutarEscaneo($sku);
                $this->scanner_code = ''; // ✅ Limpiar campo de escáner
            } else {
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