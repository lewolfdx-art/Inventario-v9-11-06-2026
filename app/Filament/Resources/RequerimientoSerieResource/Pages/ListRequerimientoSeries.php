<?php

namespace App\Filament\Resources\RequerimientoSerieResource\Pages;

use App\Filament\Resources\RequerimientoSerieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequerimientoSeries extends ListRecords
{
    protected static string $resource = RequerimientoSerieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
