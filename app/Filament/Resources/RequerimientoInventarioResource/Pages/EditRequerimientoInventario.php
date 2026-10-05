<?php

namespace App\Filament\Resources\RequerimientoInventarioResource\Pages;

use App\Filament\Resources\RequerimientoInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequerimientoInventario extends EditRecord
{
    protected static string $resource = RequerimientoInventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
