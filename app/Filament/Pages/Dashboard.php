<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Escritorio del Inventario';
    
    protected static ?string $title = 'Escritorio del Inventario';
    
    protected static ?string $navigationIcon = 'heroicon-o-home';
}