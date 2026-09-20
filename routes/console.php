<?php

use Illuminate\Support\Facades\Schedule;

// ✅ Ejecutar alertas todos los días a las 8:00 AM
Schedule::command('notificar:alertas')
    ->dailyAt('08:00')
    ->withoutOverlapping();

// ✅ O si quieres cada hora:
// Schedule::command('notificar:alertas')
//     ->hourly()
//     ->withoutOverlapping();