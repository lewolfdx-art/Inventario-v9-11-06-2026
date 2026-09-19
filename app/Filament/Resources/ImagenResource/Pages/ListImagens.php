<?php

namespace App\Filament\Resources\ImagenResource\Pages;

use App\Filament\Resources\ImagenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImagens extends ListRecords
{
    protected static string $resource = ImagenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
