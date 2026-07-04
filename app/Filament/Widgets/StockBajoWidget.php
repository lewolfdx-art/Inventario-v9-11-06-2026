<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockBajoWidget extends BaseWidget
{
    protected function getHeading(): string
    {
        return ' Stock Bajo';
    }

    protected function getStats(): array
    {
        // Productos con stock bajo (5 o menos)
        $productosBajo = Producto::where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $sinStock = Producto::where('stock', 0)
            ->orderBy('nombre', 'asc')
            ->limit(5)
            ->get();

        $totalBajo = Producto::where('stock', '<=', 5)->where('stock', '>', 0)->count();
        $totalSinStock = Producto::where('stock', 0)->count();
        $stockCritico = Producto::where('stock', '<=', 3)->count();

        // Obtener productos con stock bajo para mostrar
        $productosLista = $productosBajo->pluck('nombre')->map(function ($nombre, $key) use ($productosBajo) {
            $stock = $productosBajo[$key]->stock;
            return "{$nombre} ({$stock})";
        })->implode(', ') ?: 'Sin productos con stock bajo';

        $sinStockLista = $sinStock->pluck('nombre')->implode(', ') ?: 'Sin productos sin stock';

        return [
            Stat::make('⚠️ Stock Crítico (≤ 3)', $stockCritico)
                ->description('Productos con stock muy bajo')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->chart([4, 3, 5, 2, 6, 3, 4]),

            Stat::make('📉 Stock Bajo (≤ 5)', $totalBajo)
                ->description('Productos con stock bajo')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning')
                ->chart([2, 4, 3, 5, 4, 6, 3]),

            Stat::make('🚫 Sin Stock', $totalSinStock)
                ->description('Productos agotados')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart([1, 2, 1, 3, 2, 4, 2]),

            Stat::make(' Productos con stock bajo', '')
                ->description($productosLista)
                ->descriptionIcon('heroicon-m-list-bullet')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'text-sm font-normal', // Reduce el tamaño de la letra
                ]),
        ];
    }
}