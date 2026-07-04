<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RecalibracionPendienteWidget extends BaseWidget
{
    protected function getHeading(): string
    {
        return '🔄 Equipos que necesitan recalibración';
    }

    protected function getStats(): array
    {
        $hoy = Carbon::now()->startOfDay();
        $fechaLimite = $hoy->copy()->addDays(7);

        // Productos con recalibración próxima o vencida - ÚLTIMOS 3
        $productos = Producto::whereHas('recalibraciones', function ($query) use ($hoy, $fechaLimite) {
            $query->whereNotNull('proxima_recalibracion')
                ->where(function ($q) use ($hoy, $fechaLimite) {
                    $q->whereDate('proxima_recalibracion', '<=', $fechaLimite)
                        ->orWhereDate('proxima_recalibracion', '<', $hoy);
                });
        })
        ->with(['recalibraciones' => function ($query) {
            $query->whereNotNull('proxima_recalibracion')
                ->orderBy('proxima_recalibracion', 'asc');
        }])
        ->orderBy('created_at', 'desc')  // ← Ordena por fecha de creación (más reciente primero)
        ->limit(3)                       // ← Solo 3 productos
        ->get();

        // Contar totales
        $vencidos = 0;
        $porVenir = 0;
        $hoyCount = 0;
        $nombresProductos = [];

        foreach ($productos as $producto) {
            $proxima = $producto->recalibraciones->first()?->proxima_recalibracion;
            if ($proxima) {
                $dias = $hoy->diffInDays($proxima, false);
                if ($dias < 0) {
                    $vencidos++;
                } elseif ($dias == 0) {
                    $hoyCount++;
                } else {
                    $porVenir++;
                }
                $nombresProductos[] = $producto->nombre;
            }
        }

        // Obtener los productos para mostrar (todos los que tenemos, que son 3)
        $listaProductos = !empty($nombresProductos) 
            ? implode(', ', $nombresProductos) 
            : 'Sin equipos próximos';

        return [
            Stat::make('🔴 Vencidos', $vencidos)
                ->description('Equipos con recalibración vencida')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->chart([4, 3, 5, 2, 6, 3, 4]),

            Stat::make('🟡 Hoy', $hoyCount)
                ->description('Equipos que vencen hoy')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([2, 3, 1, 4, 2, 3, 1]),

            Stat::make('🟢 Próximos (7 días)', $porVenir)
                ->description('Equipos que vencen en los próximos 7 días')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([1, 2, 3, 2, 4, 3, 5]),

            Stat::make('📋 Últimos equipos a vencer', '')
                ->description($listaProductos)
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'text-sm font-normal',
                ]),
        ];
    }
}