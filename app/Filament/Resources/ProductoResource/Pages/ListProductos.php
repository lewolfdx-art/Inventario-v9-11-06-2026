<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Producto;
use App\Models\Movimiento;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    public $contador_escaneos = 0;
    public $scanner_code = '';

    protected function getHeaderActions(): array
    {
        return [
            // ✅ SWITCH PARA VER PRODUCTOS SIN STOCK
            Action::make('verSinStock')
                ->label('🔴 Ver productos sin stock')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->modalHeading('🔴 Productos sin Stock')
                ->modalDescription('Productos que actualmente tienen 0 unidades disponibles')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalWidth('4xl')
                ->modalContent(function () {
                    $productos = Producto::where('stock', '<=', 0)
                        ->orderBy('nombre')
                        ->get();

                    if ($productos->isEmpty()) {
                        return new HtmlString('
                            <div class="text-center p-8">
                                <div class="text-6xl mb-4">✅</div>
                                <div class="text-xl font-bold text-green-600">No hay productos sin stock</div>
                                <div class="text-gray-500 mt-2">Todos los productos tienen stock disponible</div>
                            </div>
                        ');
                    }

                    $html = '<div class="space-y-2 max-h-[60vh] overflow-y-auto p-2" style="scrollbar-width: thin;">';
                    
                    $html .= '<div class="text-sm text-gray-500 mb-3 text-center">Total: ' . $productos->count() . ' producto(s) sin stock</div>';
                    
                    $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-3">';
                    
                    foreach ($productos as $producto) {
                        $editUrl = '/admin/productos/' . $producto->id . '/edit';
                        $nombre = e($producto->nombre);
                        $sku = e($producto->sku);
                        $modelo = e($producto->modelo ?? 'N/A');
                        $marca = e($producto->marca?->nombre ?? 'N/A');
                        
                        $html .= <<<HTML
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border-2 border-red-200 dark:border-red-800 shadow-sm hover:shadow-md transition">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-sm text-gray-900 dark:text-white truncate">{$nombre}</div>
                                        <div class="text-xs text-gray-500 mt-1">SKU: <span class="font-mono">{$sku}</span></div>
                                        <div class="text-xs text-gray-500">Modelo: {$modelo}</div>
                                        <div class="text-xs text-gray-500">Marca: {$marca}</div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-300 font-bold text-base">
                                            0
                                        </span>
                                    </div>
                                </div>
                                <a href="{$editUrl}" class="mt-2 block text-center text-xs bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-1.5 px-3 rounded-lg transition">
                                    ✏️ Editar
                                </a>
                            </div>
                        HTML;
                    }
                    
                    $html .= '</div></div>';
                    
                    return new HtmlString($html);
                }),

            Actions\CreateAction::make()
                ->label('Nuevo Producto')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function updatedScannerCode($value): void
    {
        if (!empty($value) && strlen($value) > 3) {
            $sku = trim($value);
            $sku = ltrim($sku, ':>');
            $sku = trim($sku);
            $sku = str_replace(["'", "´", "`", '"', ';'], '-', $sku);
            $sku = preg_replace('/[^a-zA-Z0-9\-]/', '', $sku);
            
            $producto = Producto::where('sku', $sku)->first();
            
            if ($producto) {
                $this->ejecutarEscaneo($sku);
                $this->scanner_code = '';
            } else {
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
        if (empty($sku)) return;

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