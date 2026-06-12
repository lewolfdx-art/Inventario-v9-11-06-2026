<?php

namespace App\Filament\Resources\RequerimientoCalibracionResource\Pages;

use App\Filament\Resources\RequerimientoCalibracionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequerimientoCalibracion extends EditRecord
{
    protected static string $resource = RequerimientoCalibracionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}