<?php

namespace App\Filament\Resources\MaletinResource\Pages;

use App\Filament\Resources\MaletinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditMaletin extends EditRecord
{
    protected static string $resource = MaletinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash'),
        ];
    }

    /**
     * Método mount - se ejecuta al cargar la página
     * Verifica si hay una notificación pendiente en sesión
     */
    public function mount($record): void
    {
        parent::mount($record);
        
        // Verificar si hay una notificación en sesión (desde Producto)
        if (session()->has('filament_notification')) {
            $notification = session()->get('filament_notification');
            
            Notification::make()
                ->title($notification['title'] ?? 'Información')
                ->body($notification['body'] ?? '')
                ->{$notification['status'] ?? 'info'}()
                ->persistent()
                ->send();
            
            // Limpiar la sesión después de mostrar la notificación
            session()->forget('filament_notification');
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // ✅ Reordenar items al guardar
        if (isset($data['componentesEquipo']) && is_array($data['componentesEquipo'])) {
            $filtered = [];
            foreach ($data['componentesEquipo'] as $item) {
                if (!empty($item['descripcion']) || !empty($item['cantidad'])) {
                    $filtered[] = $item;
                }
            }
            foreach ($filtered as $index => &$item) {
                $item['item_numero'] = $index + 1;
                $item['incluido'] = $item['incluido'] ?? false;
            }
            $data['componentesEquipo'] = $filtered;
        }
        
        if (isset($data['accesoriosSet']) && is_array($data['accesoriosSet'])) {
            $filtered = [];
            foreach ($data['accesoriosSet'] as $item) {
                if (!empty($item['descripcion']) || !empty($item['cantidad'])) {
                    $filtered[] = $item;
                }
            }
            foreach ($filtered as $index => &$item) {
                $item['item_numero'] = $index + 1;
                $item['incluido'] = $item['incluido'] ?? false;
            }
            $data['accesoriosSet'] = $filtered;
        }
        
        if (isset($data['accesoriosAdicionales']) && is_array($data['accesoriosAdicionales'])) {
            $filtered = [];
            foreach ($data['accesoriosAdicionales'] as $item) {
                if (!empty($item['descripcion']) || !empty($item['cantidad'])) {
                    $filtered[] = $item;
                }
            }
            foreach ($filtered as $index => &$item) {
                $item['item_numero'] = $index + 1;
                $item['incluido'] = $item['incluido'] ?? false;
            }
            $data['accesoriosAdicionales'] = $filtered;
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Sobrescribir el título de la página
     */
    public function getTitle(): string
    {
        $record = $this->getRecord();
        
        if ($record) {
            return 'Editar Maletín: ' . $record->nombre;
        }
        
        return parent::getTitle();
    }
}