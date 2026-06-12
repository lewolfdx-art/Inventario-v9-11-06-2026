<?php

namespace App\Filament\Resources\UnidadCompraResource\Pages;

use App\Filament\Resources\UnidadCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnidadCompra extends EditRecord
{
    protected static string $resource = UnidadCompraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
