<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class CheckProductosAlertas extends Command
{
    protected $signature = 'productos:alertas';
    protected $description = 'Verifica productos con stock bajo y recalibraciones próximas y envía notificaciones';

    public function handle()
    {
        $this->info('🔍 Verificando alertas de productos...');
        
        NotificationService::runAllChecks();
        
        $this->info('✅ Notificaciones enviadas correctamente.');
        
        return Command::SUCCESS;
    }
}