<?php

namespace App\Filament\Resources\RequerimientoCalibracionResource\Pages;

use App\Filament\Resources\RequerimientoCalibracionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequerimientoCalibraciones extends ListRecords
{
    protected static string $resource = RequerimientoCalibracionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}