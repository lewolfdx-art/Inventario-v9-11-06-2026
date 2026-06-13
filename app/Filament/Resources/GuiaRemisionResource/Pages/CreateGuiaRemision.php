<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Filament\Resources\GuiaRemisionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuiaRemision extends CreateRecord
{
    protected static string $resource = GuiaRemisionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}