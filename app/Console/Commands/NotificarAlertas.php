<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificarAlertas extends Command
{
    protected $signature = 'notificar:alertas';
    protected $description = 'Envía notificaciones de stock bajo, sin stock y recalibraciones';

    public function handle()
    {
        $this->info('🔔 Iniciando envío de alertas...');

        try {
            $destinatarios = User::whereIn('rol', ['super_admin', 'admin', 'logistica', 'almacen'])->get();
            $this->info("👥 Destinatarios: {$destinatarios->count()}");

            if ($destinatarios->isEmpty()) {
                $this->warn('⚠️ No hay destinatarios configurados');
                return;
            }

            $this->limpiarNotificacionesViejas();

            // ==========================================
            // 🔴 1. SIN STOCK
            // ==========================================
            $sinStock = Producto::where('stock', '<=', 0)->get();
            $this->info("📦 Sin stock: {$sinStock->count()}");

            if ($sinStock->count() > 0) {
                $this->enviarNotificacion(
                    $destinatarios,
                    'sin_stock',
                    '🔴 ' . $sinStock->count() . ' productos SIN STOCK',
                    $this->getListaProductos($sinStock, 5),
                    'danger',
                    '/admin/productos'
                );
                $this->info("🔴 Sin stock notificado");
            }

            // ==========================================
            // 🟡 2. STOCK BAJO
            // ==========================================
            $stockBajo = Producto::where('stock', '>', 0)->where('stock', '<=', 5)->get();
            $this->info("📦 Stock bajo: {$stockBajo->count()}");

            if ($stockBajo->count() > 0) {
                $this->enviarNotificacion(
                    $destinatarios,
                    'stock_bajo',
                    '🟡 ' . $stockBajo->count() . ' productos con STOCK BAJO',
                    $this->getListaProductos($stockBajo, 5),
                    'warning',
                    '/admin/productos'
                );
                $this->info("🟡 Stock bajo notificado");
            }

            // ==========================================
            // 🟠 3. RECALIBRACIÓN PRÓXIMA
            // ==========================================
            $proximasRecalibraciones = Producto::whereHas('recalibraciones', function ($query) {
                $query->whereNotNull('proxima_recalibracion')
                    ->whereDate('proxima_recalibracion', '<=', now()->addDays(30))
                    ->whereDate('proxima_recalibracion', '>=', now()->startOfDay());
            })->get();

            if ($proximasRecalibraciones->count() > 0) {
                $this->enviarNotificacion(
                    $destinatarios,
                    'recalibracion_proxima',
                    '🟠 ' . $proximasRecalibraciones->count() . ' productos próximos a RECALIBRAR',
                    $this->getListaRecalibraciones($proximasRecalibraciones, 5),
                    'warning',
                    '/admin/productos'
                );
                $this->info("🟠 Recalibración próxima notificada");
            }

            // ==========================================
            // 🔴 4. RECALIBRACIÓN VENCIDA
            // ==========================================
            $recalibracionesVencidas = Producto::whereHas('recalibraciones', function ($query) {
                $query->whereNotNull('proxima_recalibracion')
                    ->whereDate('proxima_recalibracion', '<', now()->startOfDay());
            })->get();

            if ($recalibracionesVencidas->count() > 0) {
                $this->enviarNotificacion(
                    $destinatarios,
                    'recalibracion_vencida',
                    '🔴 ' . $recalibracionesVencidas->count() . ' productos con RECALIBRACIÓN VENCIDA',
                    $this->getListaRecalibraciones($recalibracionesVencidas, 5),
                    'danger',
                    '/admin/productos'
                );
                $this->info("🔴 Recalibración vencida notificada");
            }

            $this->info('✅ Alertas enviadas correctamente');
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Error en notificar:alertas', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }
    }

    /**
     * ✅ Inserta la notificación directamente en la tabla `notifications`
     */
    protected function enviarNotificacion($destinatarios, $tipo, $titulo, $body, $color, $url)
    {
        $hoy = now()->startOfDay();
        $insertados = 0;

        foreach ($destinatarios as $user) {
            // ✅ Evitar duplicados del día
            $existe = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class)
                ->whereDate('created_at', $hoy)
                ->where('data', 'LIKE', '%' . $tipo . '%')
                ->exists();

            if ($existe) {
                continue;
            }

            // ✅ Estructura compatible con Filament
            $data = [
                'title' => $titulo,
                'body' => $body,
                'color' => $color,
                'icon' => match($color) {
                    'danger' => 'heroicon-o-x-circle',
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'success' => 'heroicon-o-check-circle',
                    'info' => 'heroicon-o-information-circle',
                    default => 'heroicon-o-bell',
                },
                'iconColor' => $color,
                'status' => $color,
                'actions' => [
                    [
                        'name' => 'ver',
                        'label' => 'Ver productos',
                        'url' => $url,
                        'shouldMarkAsRead' => true,
                        'shouldOpenUrlInNewTab' => false,
                        'isButton' => true,
                    ],
                ],
                'duration' => 'persistent',
                'tipo' => $tipo,
                'format' => 'filament',
            ];

            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'Filament\\Notifications\\DatabaseNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode($data),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $insertados++;
        }

        $this->info("   → {$insertados} notificaciones insertadas");
    }

    protected function limpiarNotificacionesViejas()
    {
        $eliminadas = DB::table('notifications')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        if ($eliminadas > 0) {
            $this->info("🧹 {$eliminadas} notificaciones viejas eliminadas");
        }
    }

    protected function getListaProductos($productos, $limite = 5): string
    {
        $lista = $productos->take($limite)->map(function ($p) {
            return "• {$p->sku} - {$p->nombre} (Stock: {$p->stock})";
        })->implode("\n");

        $restantes = $productos->count() - $limite;
        if ($restantes > 0) {
            $lista .= "\n... y {$restantes} más";
        }

        return $lista;
    }

    protected function getListaRecalibraciones($productos, $limite = 5): string
    {
        $lista = $productos->take($limite)->map(function ($p) {
            $recalibracion = $p->recalibraciones()
                ->whereNotNull('proxima_recalibracion')
                ->orderBy('proxima_recalibracion', 'asc')
                ->first();

            $fecha = $recalibracion?->proxima_recalibracion?->format('d/m/Y') ?? 'N/A';

            return "• {$p->sku} - {$p->nombre} (Recalibrar: {$fecha})";
        })->implode("\n");

        $restantes = $productos->count() - $limite;
        if ($restantes > 0) {
            $lista .= "\n... y {$restantes} más";
        }

        return $lista;
    }
}