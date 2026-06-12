<?php

namespace App\Filament\Resources\RequerimientoInventarioResource\Pages;

use App\Filament\Resources\RequerimientoInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequerimientoInventarios extends ListRecords
{
    protected static string $resource = RequerimientoInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
