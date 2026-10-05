<?php

namespace App\Filament\Resources\RequerimientoInventarioResource\Pages;

use App\Filament\Resources\RequerimientoInventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRequerimientoInventario extends CreateRecord
{
    protected static string $resource = RequerimientoInventarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
