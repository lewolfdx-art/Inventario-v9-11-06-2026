<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use App\Models\Maletin;
use App\Models\Marca;
use App\Models\Categoria;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenGeneralWidget extends BaseWidget
{
    protected function getHeading(): string
    {
        return ' Resumen General';
    }
    protected function getStats(): array
    {
        $totalProductos = Producto::count();
        $totalMaletines = Maletin::count();
        $totalMarcas = Marca::count();
        $totalCategorias = Categoria::count();

        // Stock crítico y sin stock
        $stockCritico = Producto::where('stock', '<=', 3)->count();
        $sinStock = Producto::where('stock', 0)->count();

        return [
            Stat::make('Total Productos', $totalProductos)
                ->description('Registrados en el sistema')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total Maletines', $totalMaletines)
                ->description('Activos en el sistema')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success')
                ->chart([3, 5, 2, 7, 4, 6, 8]),

            Stat::make('Total Marcas', $totalMarcas)
                ->description('Marcas registradas')
                ->descriptionIcon('heroicon-m-tag')
                ->color('info')
                ->chart([2, 4, 3, 6, 5, 7, 5]),

            Stat::make('Total Categorías', $totalCategorias)
                ->description('Categorías de productos')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('warning')
                ->chart([1, 3, 2, 4, 3, 5, 4]),

            Stat::make('Stock Crítico', $stockCritico)
                ->description('Productos con stock ≤ 3')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Sin Stock', $sinStock)
                ->description('Productos agotados')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}