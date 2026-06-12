<?php

namespace App\Filament\Resources\RequerimientoLoteResource\Pages;

use App\Filament\Resources\RequerimientoLoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequerimientoLote extends EditRecord
{
    protected static string $resource = RequerimientoLoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
