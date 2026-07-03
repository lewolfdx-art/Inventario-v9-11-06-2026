<?php
// app/Filament/Resources/MaletinResource/Pages/EditMaletin.php

namespace App\Filament\Resources\MaletinResource\Pages;

use App\Filament\Resources\MaletinResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}