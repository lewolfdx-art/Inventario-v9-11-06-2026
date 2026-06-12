<?php

namespace App\Filament\Resources\NaturalezaResource\Pages;

use App\Filament\Resources\NaturalezaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNaturalezas extends ListRecords
{
    protected static string $resource = NaturalezaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
