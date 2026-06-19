<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use App\Models\Movimiento;
use Filament\Widgets\Widget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class ScannerWidget extends Widget
{
    protected static ?int $sort = -10;
    protected static string $view = 'filament.widgets.scanner-widget';
    protected static bool $isDiscovered = false;
    public $sku = '';
    public $tipo = 'salida'; // ✅ Empieza en SALIDA
    public $cantidad = 1;
    public $ultimo_escaneo = null;
    public $contador_escaneos = 0; // ✅ Contador para alternar

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('📋 Código de barras')
                    ->placeholder('Escanea el código...')
                    ->autofocus()
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        if (!empty($state)) {
                            $this->procesarEscaneo();
                        }
                    }),

                // ✅ Mostrar el modo actual (solo lectura)
                Forms\Components\TextInput::make('tipo_mostrar')
                    ->label('Modo actual')
                    ->disabled()
                    ->default(fn() => '📤 SALIDA')
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        // No hace nada, solo muestra
                    }),

                Forms\Components\TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
            ]);
    }

    public function procesarEscaneo()
    {
        if (empty($this->sku)) {
            return;
        }

        // ✅ Limpiar el SKU escaneado
        $sku_limpio = str_replace(["'", "´", "`"], '-', $this->sku);

        // Buscar el producto por SKU limpio
        $producto = Producto::where('sku', $sku_limpio)->first();

        if (!$producto) {
            Notification::make()
                ->title('❌ Producto no encontrado')
                ->body('No se encontró ningún producto con SKU: ' . $sku_limpio)
                ->danger()
                ->send();

            $this->sku = '';
            return;
        }

        // ✅ ALTERNAR AUTOMÁTICAMENTE: SALIDA → ENTRADA → SALIDA → ENTRADA
        $this->contador_escaneos++;

        if ($this->contador_escaneos % 2 == 1) {
            // Escaneo IMPAR → SALIDA
            $this->tipo = 'salida';
            $icono = '📤';
            $color = 'warning';
            $mensaje = 'SALIDA';
        } else {
            // Escaneo PAR → ENTRADA
            $this->tipo = 'entrada';
            $icono = '📥';
            $color = 'success';
            $mensaje = 'ENTRADA';
        }

        // Calcular nuevo stock
        $stockAnterior = $producto->stock ?? 0;
        $cantidad = $this->cantidad ?? 1;

        if ($this->tipo === 'entrada') {
            $stockNuevo = $stockAnterior + $cantidad;
        } else {
            if ($stockAnterior <= 0) {
                Notification::make()
                    ->title('❌ Sin stock disponible')
                    ->body('No hay stock para dar salida a ' . $producto->nombre)
                    ->danger()
                    ->send();

                $this->sku = '';
                return;
            }
            $stockNuevo = $stockAnterior - $cantidad;
        }

        // Actualizar stock del producto
        $producto->stock = $stockNuevo;
        $producto->save();

        // Registrar movimiento
        Movimiento::create([
            'producto_id' => $producto->id,
            'tipo' => $this->tipo,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
        ]);

        // Guardar último escaneo
        $this->ultimo_escaneo = [
            'producto' => $producto,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'tipo' => $this->tipo,
            'numero' => $this->contador_escaneos,
        ];

        // Mostrar notificación
        Notification::make()
            ->title($icono . ' ' . $mensaje . ' #' . $this->contador_escaneos . ' registrada')
            ->body($producto->nombre . ' | Stock: ' . $stockAnterior . ' → ' . $stockNuevo)
            ->$color()
            ->send();

        // Limpiar campo para el siguiente escaneo
        $this->sku = '';
    }

    // ✅ Método para reiniciar el contador (opcional)
    public function reiniciarContador()
    {
        $this->contador_escaneos = 0;
        $this->tipo = 'salida';
        
        Notification::make()
            ->title('🔄 Contador reiniciado')
            ->body('El próximo escaneo será una SALIDA')
            ->info()
            ->send();
    }
}