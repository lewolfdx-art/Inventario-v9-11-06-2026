<?php

namespace App\Observers;

use App\Models\Producto;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class ProductoObserver
{
    public function created(Producto $producto): void
    {
        $this->logActivity($producto, 'creado');
        
        if ($producto->stock <= 5) {
            NotificationService::checkStockBajo();
        }
        
        if ($producto->proxima_recalibracion) {
            NotificationService::checkRecalibraciones();
        }
    }

    public function updated(Producto $producto): void
    {
        $this->logActivity($producto, 'actualizado');
        
        if ($producto->wasChanged('stock') && $producto->stock <= 5) {
            NotificationService::checkStockBajo();
        }
        
        if ($producto->wasChanged('proxima_recalibracion')) {
            NotificationService::checkRecalibraciones();
        }
    }

    public function deleted(Producto $producto): void
    {
        $this->logActivity($producto, 'eliminado');
        NotificationService::checkStockBajo();
        NotificationService::checkRecalibraciones();
    }

    public function restored(Producto $producto): void
    {
        $this->logActivity($producto, 'restaurado');
        NotificationService::checkStockBajo();
        NotificationService::checkRecalibraciones();
    }

    public function forceDeleted(Producto $producto): void
    {
        $this->logActivity($producto, 'eliminado permanentemente');
    }

    private function logActivity(Producto $producto, string $event): void
    {
        $changes = $producto->getDirty();
        $original = $producto->getOriginal();
        
        $changesFormatted = [];
        foreach ($changes as $key => $value) {
            $oldValue = $original[$key] ?? 'null';
            $changesFormatted[] = "{$key}: {$oldValue} → {$value}";
        }

        // ✅ USAR FACADE Auth
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        $userName = $user ? $user->name : 'Sistema';

        activity()
            ->performedOn($producto)
            ->causedBy($userId)
            ->withProperties([
                'event' => $event,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_name' => $userName,
                'changes' => $changes,
                'original' => $original,
                'changes_formatted' => $changesFormatted,
            ])
            ->log("Producto #{$producto->sku} ({$producto->nombre}) {$event}");
    }
}