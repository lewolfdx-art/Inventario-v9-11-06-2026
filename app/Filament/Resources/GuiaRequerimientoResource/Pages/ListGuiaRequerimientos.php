<?php

namespace App\Filament\Resources\GuiaRequerimientoResource\Pages;

use App\Filament\Resources\GuiaRequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ListGuiaRequerimientos extends ListRecords
{
    protected static string $resource = GuiaRequerimientoResource::class;

    public bool $modoAutomatico = true;

    public function mount(): void
    {
        parent::mount();
        // ✅ Cargar el estado del switch desde la sesión (default: activado)
        $this->modoAutomatico = session('guia_codigo_automatico', true);
    }

    protected function getHeaderActions(): array
    {
        return [
            // ✅ SWITCH PARA ACTIVAR/DESACTIVAR CÓDIGO AUTOMÁTICO
            Action::make('toggleCodigoAutomatico')
                ->label(fn () => $this->modoAutomatico
                    ? '🔒 Código automático: ACTIVADO'
                    : '🔓 Código automático: DESACTIVADO'
                )
                ->color(fn () => $this->modoAutomatico ? 'success' : 'warning')
                ->icon(fn () => $this->modoAutomatico
                    ? 'heroicon-o-lock-closed'
                    : 'heroicon-o-lock-open'
                )
                ->action(function () {
                    $this->modoAutomatico = !$this->modoAutomatico;
                    session(['guia_codigo_automatico' => $this->modoAutomatico]);

                    Notification::make()
                        ->title($this->modoAutomatico
                            ? '🔒 Código automático ACTIVADO'
                            : '🔓 Código automático DESACTIVADO'
                        )
                        ->body($this->modoAutomatico
                            ? 'Los códigos se generarán automáticamente (LOG-FOR-002, LOG-FOR-003, ...)'
                            : 'Ahora puedes escribir el código manualmente'
                        )
                        ->color($this->modoAutomatico ? 'success' : 'warning')
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('➕ Nueva Guía'),
        ];
    }
}