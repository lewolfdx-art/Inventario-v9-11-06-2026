<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Escritorio del Inventario';
    
    protected static ?string $title = 'Escritorio del Inventario';
    
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected function getHeaderActions(): array
    {
        return [
            // ✅ BOTÓN: IR AL ESCÁNER (misma ventana)
            Action::make('ir_escaner')
                ->label('📷 Inicio de Escaneo')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->url('/escanear'),  // ← Sin shouldOpenInNewTab

            // ✅ BOTÓN: REFRESCAR ALERTAS
            Action::make('refrescar_alertas')
                ->label('🔄 Refrescar Alertas')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('🔄 Refrescar Alertas')
                ->modalDescription('Se ejecutará el análisis de stock y recalibraciones.')
                ->modalSubmitActionLabel('Sí, refrescar')
                ->action(function () {
                    try {
                        Artisan::call('notificar:alertas');

                        Notification::make()
                            ->title('✅ Alertas actualizadas')
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('❌ Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}