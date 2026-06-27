<?php

namespace App\Services;

use App\Models\Producto;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class NotificationService
{
    /**
     * Verificar y notificar productos con stock bajo
     */
    public static function checkStockBajo()
    {
        // Productos con stock <= 5 (incluyendo 0)
        $productos = Producto::with(['categoria', 'marca'])
            ->where('stock', '<=', 5)
            ->orderBy('stock', 'asc')
            ->get();

        if ($productos->isEmpty()) {
            return;
        }

        $totalProductos = $productos->count();
        
        // Contar usando la colección correctamente
        $stockCritico = $productos->where('stock', '<=', 0)->count();
        $stockBajo = $productos->where('stock', '>', 0)->where('stock', '<=', 5)->count();

        // Construir mensaje - SOLO RESUMEN
        $body = "RESUMEN DE STOCK BAJO\n";
        $body .= "────────────────────────────\n";
        $body .= "Total productos: {$totalProductos}\n";
        $body .= "Sin stock (crítico): {$stockCritico}\n";
        $body .= "Stock bajo (≤ 5): {$stockBajo}";

        Notification::make()
            ->title("Alerta de Stock Bajo")
            ->body($body)
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('danger')
            ->duration(60000)
            ->actions([
                Action::make('ver')
                    ->label('Ver todos los productos')
                    ->url('/admin/productos')
                    ->button(),
                Action::make('verCriticos')
                    ->label('Ver críticos (sin stock)')
                    ->url('/admin/productos?tableFilters[stock][value]=0')
                    ->color('danger'),
            ])
            ->persistent()
            ->send();
    }

    /**
     * Verificar y notificar recalibraciones próximas
     */
    public static function checkRecalibraciones()
    {
        $hoy = Carbon::now()->startOfDay();
        $treintaDias = $hoy->copy()->addDays(30);
        
        // Obtener productos con recalibraciones próximas
        $productosConRecalibracion = Producto::whereHas('recalibraciones', function ($query) use ($hoy, $treintaDias) {
            $query->whereNotNull('proxima_recalibracion')
                ->whereDate('proxima_recalibracion', '>=', $hoy->toDateString())
                ->whereDate('proxima_recalibracion', '<=', $treintaDias->toDateString());
        })
        ->with(['recalibraciones' => function ($query) use ($hoy, $treintaDias) {
            $query->whereNotNull('proxima_recalibracion')
                ->whereDate('proxima_recalibracion', '>=', $hoy->toDateString())
                ->whereDate('proxima_recalibracion', '<=', $treintaDias->toDateString())
                ->orderBy('proxima_recalibracion', 'asc');
        }])
        ->get();

        if ($productosConRecalibracion->isEmpty()) {
            return;
        }

        // Arrays para almacenar los productos
        $vencidas = [];
        $proximas7Dias = [];
        $proximas30Dias = [];

        foreach ($productosConRecalibracion as $producto) {
            foreach ($producto->recalibraciones as $recalibracion) {
                if (!$recalibracion->proxima_recalibracion) continue;
                
                $fecha = Carbon::parse($recalibracion->proxima_recalibracion)->startOfDay();
                $diferencia = (int) $hoy->diffInDays($fecha, false);
                
                if ($diferencia < 0) {
                    $vencidas[] = $producto;
                    break;
                } elseif ($diferencia <= 7) {
                    $proximas7Dias[] = $producto;
                    break;
                } elseif ($diferencia <= 30) {
                    $proximas30Dias[] = $producto;
                }
            }
        }

        $totalVencidas = count($vencidas);
        $total7Dias = count($proximas7Dias);
        $total30Dias = count($proximas30Dias);
        $total = $totalVencidas + $total7Dias + $total30Dias;

        if ($total === 0) {
            return;
        }

        // Construir mensaje - SOLO RESUMEN
        $mensaje = "RESUMEN DE RECALIBRACIONES\n";
        $mensaje .= "────────────────────────────\n";
        $mensaje .= "Total: {$total} productos\n";
        
        if ($totalVencidas > 0) {
            $mensaje .= "Vencidas: {$totalVencidas}\n";
        }
        if ($total7Dias > 0) {
            $mensaje .= "Próximos 7 días: {$total7Dias}\n";
        }
        if ($total30Dias > 0) {
            $mensaje .= "Próximos 30 días: {$total30Dias}";
        }

        $iconColor = $totalVencidas > 0 ? 'danger' : ($total7Dias > 0 ? 'warning' : 'info');

        Notification::make()
            ->title("Alertas de Recalibración")
            ->body($mensaje)
            ->icon('heroicon-o-calendar')
            ->iconColor($iconColor)
            ->duration(60000)
            ->actions([
                Action::make('ver')
                    ->label('Ver todos los productos')
                    ->url('/admin/productos')
                    ->button(),
                Action::make('verVencidas')
                    ->label('Ver vencidas')
                    ->url('/admin/productos?tableFilters[estado][value]=vencido')
                    ->color('danger'),
            ])
            ->persistent()
            ->send();
    }

    /**
     * Ejecutar todas las verificaciones
     */
    public static function runAllChecks()
    {
        self::checkStockBajo();
        self::checkRecalibraciones();
    }
}