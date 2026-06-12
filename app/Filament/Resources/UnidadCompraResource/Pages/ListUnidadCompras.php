<?php

namespace App\Filament\Resources\UnidadCompraResource\Pages;

use App\Filament\Resources\UnidadCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnidadCompras extends ListRecords
{
    protected static string $resource = UnidadCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
