<?php

namespace App\Filament\Widgets;

use App\Models\Maletin;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenMaletinesWidget extends BaseWidget
{
    protected function getHeading(): string
    {
        return ' Resumen de Maletines';
    }

    protected function getStats(): array
    {
        $total = Maletin::count();
        
        $porEstado = Maletin::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        $estados = [
            'activo' => 0,
            'prestado' => 0,
            'devuelto' => 0,
            'mantenimiento' => 0,
        ];

        foreach ($porEstado as $item) {
            $estados[$item->estado] = $item->total;
        }

        // Maletines con productos asociados
        $conProductos = Maletin::whereHas('productos')->count();
        $sinProductos = $total - $conProductos;

        // Últimos maletines creados
        $ultimos = Maletin::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $ultimosNombres = $ultimos->pluck('nombre')->implode(', ') ?: 'Sin maletines';

        return [
            Stat::make('🧳 Total Maletines', $total)
                ->description('Maletines registrados')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary')
                ->chart([5, 7, 4, 8, 6, 9, 7]),

            Stat::make('✅ Activos', $estados['activo'] ?? 0)
                ->description('Maletines disponibles')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([3, 4, 5, 4, 6, 5, 7]),

            Stat::make('📤 Prestados', $estados['prestado'] ?? 0)
                ->description('Maletines en préstamo')
                ->descriptionIcon('heroicon-m-arrow-right-circle')
                ->color('warning')
                ->chart([2, 3, 2, 4, 3, 5, 4]),

            Stat::make('🔧 Mantenimiento', $estados['mantenimiento'] ?? 0)
                ->description('Maletines en mantenimiento')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('danger')
                ->chart([1, 2, 1, 3, 2, 4, 2]),

            Stat::make('🔗 Con Productos', $conProductos)
                ->description('Maletines con productos asociados')
                ->descriptionIcon('heroicon-m-link')
                ->color('info')
                ->chart([4, 5, 6, 5, 7, 6, 8]),

            Stat::make('📋 Últimos maletines', '')
                ->description($ultimosNombres)
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'text-sm font-normal', // Reduce el tamaño de la letra
                ]),
        ];
    }
}