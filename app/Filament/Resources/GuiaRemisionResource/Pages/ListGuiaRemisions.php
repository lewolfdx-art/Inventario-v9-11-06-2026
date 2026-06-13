<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Filament\Resources\GuiaRemisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuiasRemisions extends ListRecords
{
    protected static string $resource = GuiaRemisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Guía')
                ->icon('heroicon-o-plus'),
        ];
    }
}