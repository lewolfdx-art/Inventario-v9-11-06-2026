<?php

namespace App\Filament\Resources\GuiaRequerimientoResource\Pages;

use App\Filament\Resources\GuiaRequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuiaRequerimiento extends EditRecord
{
    protected static string $resource = GuiaRequerimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
