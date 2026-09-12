<?php

namespace App\Providers;

use App\Support\Utf8Cleaner;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class LivewireUtf8ServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ✅ Intercepta TODOS los requests de Livewire
        Livewire::listen('request', function ($event) {
            $payload = $event->payload ?? null;
            if (!$payload) {
                return;
            }

            // Sanitizar el payload COMPLETO antes de que Livewire lo procese
            $cleaned = Utf8Cleaner::clean((array) $payload);

            // Asignar de vuelta (Livewire v3 usa propiedades públicas)
            if (is_object($event) && property_exists($event, 'payload')) {
                $event->payload = $cleaned;
            }
        });
    }
}