<?php

namespace App\Filament\Resources\GuiaRequerimientoResource\Pages;

use App\Filament\Resources\GuiaRequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuiaRequerimientos extends ListRecords
{
    protected static string $resource = GuiaRequerimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
