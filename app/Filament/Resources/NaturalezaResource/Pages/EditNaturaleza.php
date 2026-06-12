<?php

namespace App\Filament\Resources\NaturalezaResource\Pages;

use App\Filament\Resources\NaturalezaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNaturaleza extends EditRecord
{
    protected static string $resource = NaturalezaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
