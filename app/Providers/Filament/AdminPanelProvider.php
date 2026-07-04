<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Sistema de Inventario')
            ->brandLogo(asset('images/login-logo.png'))
            ->brandLogoHeight('3rem')
            
            // ✅ SOLO CAMBIÉ EL COLOR A AMARILLO
            ->colors([
                'primary' => Color::Yellow,
            ])
            
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            
            // ✅ DESCUBRIMIENTO AUTOMÁTICO (esto ya funciona)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            
            // ✅ SOLO ELIMINÉ AccountWidget Y FilamentInfoWidget
            ->widgets([
                // Widgets\AccountWidget::class,    ← ELIMINADO
                // Widgets\FilamentInfoWidget::class, ← ELIMINADO
            ])
            
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            
            ->authMiddleware([
                Authenticate::class,
            ])
            
            ->bootUsing(function () {
                if (request()->is('admin*') && !Session::has('alertas_ejecutadas')) {
                    try {
                        NotificationService::runAllChecks();
                        Session::put('alertas_ejecutadas', true);
                    } catch (\Exception $e) {
                        Log::error('Error en notificaciones: ' . $e->getMessage());
                    }
                }
            });
    }
}