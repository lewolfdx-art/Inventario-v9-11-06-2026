<?php
// app/Filament/Resources/MaletinResource/Pages/ListMaletines.php

namespace App\Filament\Resources\MaletinResource\Pages;

use App\Filament\Resources\MaletinResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaletines extends ListRecords
{
    protected static string $resource = MaletinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Maletín')  // ✅ Texto del botón
                ->icon('heroicon-o-plus')  // ✅ Icono
                ->slideOver(),  // ✅ Opcional: abre en modal lateral
        ];
    }

    public function getHeading(): string
    {
        return 'Listado de Maletines';
    }
}