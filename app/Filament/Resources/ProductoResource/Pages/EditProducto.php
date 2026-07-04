<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Maletin;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditProducto extends EditRecord
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }

    /**
     * Método principal que maneja la redirección
     * Este se ejecuta ANTES de que se renderice la página
     */
    public function mount($record): void
    {
        // Primero llamamos al mount padre
        parent::mount($record);
        
        // Verificar si es un maletín y redirigir inmediatamente
        $record = $this->getRecord();
        
        if ($record && $this->esMaletin($record)) {
            $maletinId = $this->obtenerIdMaletin($record);
            
            if ($maletinId) {
                Log::info("Redirigiendo producto ID {$record->id} a maletín ID {$maletinId}");
                
                // Guardar notificación en sesión para mostrarla en la página de destino
                session()->flash('filament_notification', [
                    'title' => 'Producto pertenece a un maletín',
                    'body' => "Este producto pertenece al maletín. Serás redirigido automáticamente.",
                    'status' => 'info',
                ]);
                
                // Redirigir al edit del maletín
                if (class_exists(\App\Filament\Resources\MaletinResource::class)) {
                    $this->redirect(\App\Filament\Resources\MaletinResource::getUrl('edit', ['record' => $maletinId]));
                } else {
                    $this->redirect(route('filament.resources.maletin.edit', $maletinId));
                }
                return;
            }
            
            // Si no se encuentra el maletín, mostrar advertencia
            Log::warning("No se encontró maletín para producto ID {$record->id}");
            Notification::make()
                ->title('No se encontró el maletín asociado')
                ->body('Redirigiendo a productos...')
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        $record = $this->getRecord();
        
        // Verificar si es un maletín
        if ($this->esMaletin($record)) {
            $maletinId = $this->obtenerIdMaletin($record);
            
            if ($maletinId) {
                Log::info("Redirigiendo producto ID {$record->id} a maletín ID {$maletinId}");
                
                if (class_exists(\App\Filament\Resources\MaletinResource::class)) {
                    return \App\Filament\Resources\MaletinResource::getUrl('edit', ['record' => $maletinId]);
                }
                return route('filament.resources.maletin.edit', $maletinId);
            }
            
            // Si no se encuentra el maletín, mostrar advertencia
            Log::warning("No se encontró maletín para producto ID {$record->id}");
            Notification::make()
                ->title('No se encontró el maletín asociado')
                ->body('Redirigiendo a productos...')
                ->warning()
                ->send();
        }
        
        return ProductoResource::getUrl('index');
    }

    /**
     * Verificar si el producto es un maletín (usando categoria_id y subcategoria_id)
     */
    private function esMaletin($producto): bool
    {
        // Verificar por categoria_id
        if (isset($producto->categoria_id) && $producto->categoria_id) {
            $categoria = Categoria::find($producto->categoria_id);
            if ($categoria && ($categoria->nombre === 'Maletín' || $categoria->nombre === 'Maleta')) {
                return true;
            }
        }
        
        // Verificar por subcategoria_id
        if (isset($producto->subcategoria_id) && $producto->subcategoria_id) {
            $subcategoria = Subcategoria::find($producto->subcategoria_id);
            if ($subcategoria && ($subcategoria->nombre === 'Maletín' || $subcategoria->nombre === 'Maleta')) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtener el ID del maletín asociado al producto
     */
    private function obtenerIdMaletin($producto): ?int
    {
        // MÉTODO 1: Buscar en la tabla pivote maletin_producto
        $pivot = DB::table('maletin_producto')
            ->where('producto_id', $producto->id)
            ->first();
        
        if ($pivot && isset($pivot->maletin_id)) {
            return $pivot->maletin_id;
        }
        
        // MÉTODO 2: Buscar por SKU (si coinciden)
        if (isset($producto->sku) && $producto->sku) {
            $maletin = Maletin::where('sku', $producto->sku)->first();
            if ($maletin) {
                return $maletin->id;
            }
        }
        
        // MÉTODO 3: Buscar por el nombre en la descripción
        // Ej: "Producto creado desde maletín: Prueba maletin n1"
        if (isset($producto->descripcion) && $producto->descripcion) {
            preg_match('/Producto creado desde maletín: (.+)/', $producto->descripcion, $matches);
            if (isset($matches[1])) {
                $nombreMaletin = trim($matches[1]);
                $maletin = Maletin::where('nombre', $nombreMaletin)->first();
                if ($maletin) {
                    return $maletin->id;
                }
            }
        }
        
        // MÉTODO 4: Buscar por nombre (coincidencia exacta)
        if (isset($producto->nombre) && $producto->nombre) {
            $maletin = Maletin::where('nombre', $producto->nombre)
                ->where('estado', 'activo')
                ->first();
            if ($maletin) {
                return $maletin->id;
            }
        }
        
        // MÉTODO 5: Usar la relación many-to-many si está definida
        if (method_exists($producto, 'maletines') && $producto->maletines()->exists()) {
            $maletin = $producto->maletines()->first();
            if ($maletin) {
                return $maletin->id;
            }
        }
        
        return null;
    }

    /**
     * Mostrar notificación después de guardar
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();
        
        if ($record && $this->esMaletin($record)) {
            $maletinId = $this->obtenerIdMaletin($record);
            if ($maletinId) {
                $maletin = Maletin::find($maletinId);
                if ($maletin) {
                    Notification::make()
                        ->title('Producto actualizado')
                        ->body("Redirigiendo al maletín: {$maletin->nombre}")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Producto actualizado')
                        ->body('Redirigiendo al editor de maletines...')
                        ->success()
                        ->send();
                }
            }
        }
    }

    /**
     * Sobrescribir el título de la página
     */
    public function getTitle(): string
    {
        $record = $this->getRecord();
        
        if ($record && $this->esMaletin($record)) {
            return 'Editar Maletín';
        }
        
        return parent::getTitle();
    }
}