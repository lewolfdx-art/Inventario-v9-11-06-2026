<?php
// app/Filament/Resources/MaletinResource/Pages/CreateMaletin.php

namespace App\Filament\Resources\MaletinResource\Pages;

use App\Filament\Resources\MaletinResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaletin extends CreateRecord
{
    protected static string $resource = MaletinResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ✅ Asegurar que TODOS los items tengan números en orden
        if (isset($data['componentesEquipo']) && is_array($data['componentesEquipo'])) {
            // Filtrar items vacíos (sin descripcion) y reindexar
            $filtered = [];
            foreach ($data['componentesEquipo'] as $item) {
                if (!empty($item['descripcion']) || !empty($item['cantidad'])) {
                    $filtered[] = $item;
                }
            }
            // Reasignar números de item en orden
            foreach ($filtered as $index => &$item) {
                $item['item_numero'] = $index + 1;
                // Asegurar que incluido tenga valor
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

    // ✅ Cambiar el título de la página
    public function getHeading(): string
    {
        return 'Nuevo Maletín';
    }

    // ✅ Cambiar el texto del botón de guardar
    protected function getCreateButtonLabel(): string
    {
        return 'Guardar Maletín';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}