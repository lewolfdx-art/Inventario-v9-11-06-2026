<?php

namespace App\Filament\Resources\GuiaRequerimientoResource\Pages;

use App\Filament\Resources\GuiaRequerimientoResource;
use App\Models\GuiaRequerimiento;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ListGuiaRequerimientos extends ListRecords
{
    protected static string $resource = GuiaRequerimientoResource::class;

    // ✅ Vista personalizada
    protected static string $view = 'filament.resources.guia-requerimiento-resource.pages.list-guia-requerimientos';

    public bool $modoAutomatico = true;
    public bool $mostrarHistorial = false;

    public function mount(): void
    {
        parent::mount();
        $this->modoAutomatico = session('guia_codigo_automatico', true);
        $this->mostrarHistorial = session('guia_mostrar_historial', false);
    }

    protected function getHeaderActions(): array
    {
        return [
            // ✅ SWITCH: HISTORIAL
            Action::make('toggleHistorial')
                ->label(fn () => $this->mostrarHistorial
                    ? '📋 Ocultar Historial'
                    : '📊 Ver Historial'
                )
                ->color(fn () => $this->mostrarHistorial ? 'danger' : 'info')
                ->icon(fn () => $this->mostrarHistorial
                    ? 'heroicon-o-eye-slash'
                    : 'heroicon-o-chart-bar'
                )
                ->action(function () {
                    $this->mostrarHistorial = !$this->mostrarHistorial;
                    session(['guia_mostrar_historial' => $this->mostrarHistorial]);

                    Notification::make()
                        ->title($this->mostrarHistorial
                            ? '📊 Historial ACTIVADO'
                            : '📊 Historial DESACTIVADO'
                        )
                        ->color($this->mostrarHistorial ? 'info' : 'warning')
                        ->send();
                }),

            // ✅ SWITCH: CÓDIGO AUTOMÁTICO
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
                            ? 'Los códigos se generarán automáticamente'
                            : 'Ahora puedes escribir el código manualmente'
                        )
                        ->color($this->modoAutomatico ? 'success' : 'warning')
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('➕ Nueva Guía'),
        ];
    }

    /**
     * ✅ Datos que se envían a la vista
     */
    protected function getViewData(): array
    {
        if (!$this->mostrarHistorial) {
            return [
                'mostrarHistorial' => false,
                'totales' => [],
                'historial' => collect(),
            ];
        }

        // ✅ Totales por estado
        $estados = ['borrador', 'pendiente', 'aprobado', 'entregado', 'devuelto'];
        $totales = [];
        foreach ($estados as $estado) {
            $totales[$estado] = GuiaRequerimiento::where('estado', $estado)->count();
        }
        $totales['total'] = GuiaRequerimiento::count();

        // ✅ Últimas 20 guías
        $historial = GuiaRequerimiento::latest('updated_at')->limit(20)->get();

        return [
            'mostrarHistorial' => true,
            'totales' => $totales,
            'historial' => $historial,
        ];
    }
}