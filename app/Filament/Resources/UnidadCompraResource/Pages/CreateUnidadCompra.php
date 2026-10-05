<?php

namespace App\Filament\Resources\UnidadCompraResource\Pages;

use App\Filament\Resources\UnidadCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnidadCompra extends CreateRecord
{
    protected static string $resource = UnidadCompraResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
