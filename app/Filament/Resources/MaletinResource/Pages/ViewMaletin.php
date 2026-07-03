<?php
// app/Filament/Resources/MaletinResource/Pages/ViewMaletin.php

namespace App\Filament\Resources\MaletinResource\Pages;

use App\Filament\Resources\MaletinResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaletin extends ViewRecord
{
    protected static string $resource = MaletinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil')
                ->color('primary'),
            
            // ✅ BOTÓN VOLVER
            Actions\Action::make('volver')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}