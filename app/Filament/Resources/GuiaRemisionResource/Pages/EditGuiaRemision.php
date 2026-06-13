<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Filament\Resources\GuiaRemisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuiaRemision extends EditRecord
{
    protected static string $resource = GuiaRemisionResource::class;

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