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

        // Productos sin stock (críticos) para mostrar
        $criticos = $productos->where('stock', '<=', 0)->take(5);
        $criticosLista = $criticos->map(function ($item) {
            return "• {$item->nombre} (SKU: {$item->sku}) - Stock: {$item->stock}";
        })->implode("\n");

        $body = "Hay {$totalProductos} productos con stock bajo:\n";
        $body .= "🚨 {$stockCritico} sin stock (crítico)\n";
        $body .= "⚠️ {$stockBajo} con stock bajo (≤ 5)";
        
        if ($stockCritico > 0) {
            $body .= "\n\nProductos sin stock:\n{$criticosLista}";
        }

        Notification::make()
            ->title(" Alerta de Stock Bajo")
            ->body($body)
            ->icon('heroicon-o-exclamation-triangle')
            ->iconColor('danger')
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
        $hoy = Carbon::now();
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
                
                $fecha = Carbon::parse($recalibracion->proxima_recalibracion);
                $diferencia = $hoy->diffInDays($fecha, false);
                
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

        // Contar usando count() en arrays (no en colecciones)
        $totalVencidas = count($vencidas);
        $total7Dias = count($proximas7Dias);
        $total30Dias = count($proximas30Dias);
        $total = $totalVencidas + $total7Dias + $total30Dias;

        if ($total === 0) {
            return;
        }

        // Construir mensaje
        $mensaje = "Hay {$total} productos con recalibraciones próximas:\n";
        
        if ($totalVencidas > 0) {
            $mensaje .= "🔴 {$totalVencidas} vencidas\n";
        }
        if ($total7Dias > 0) {
            $mensaje .= "🟡 {$total7Dias} en los próximos 7 días\n";
        }
        if ($total30Dias > 0) {
            $mensaje .= "🟢 {$total30Dias} en los próximos 30 días";
        }

        // Mostrar primeros 3 productos como ejemplo
        $primeros = array_merge($vencidas, $proximas7Dias, $proximas30Dias);
        $primeros = array_slice($primeros, 0, 3);
        
        if (!empty($primeros)) {
            $mensaje .= "\n\nPróximos ejemplos:";
            foreach ($primeros as $producto) {
                $fecha = $producto->proxima_recalibracion 
                    ? Carbon::parse($producto->proxima_recalibracion)->format('d/m/Y') 
                    : 'N/A';
                $mensaje .= "\n• {$producto->nombre} - {$fecha}";
            }
        }

        $iconColor = $totalVencidas > 0 ? 'danger' : ($total7Dias > 0 ? 'warning' : 'info');

        Notification::make()
            ->title("📅 Alertas de Recalibración")
            ->body($mensaje)
            ->icon('heroicon-o-calendar')
            ->iconColor($iconColor)
            ->actions([
                Action::make('ver')
                    ->label('Ver productos')
                    ->url('/admin/productos')
                    ->button(),
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