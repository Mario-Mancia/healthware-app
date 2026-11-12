<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas
|--------------------------------------------------------------------------
| En desarrollo se ejecutan con:  php artisan schedule:work
| En producción basta una entrada de cron que ejecute cada minuto:
|   php artisan schedule:run
*/

// Recordatorios y avisos de vencimiento de las tareas personales.
Schedule::command('tareas:recordatorios')
    ->everyMinute()
    ->withoutOverlapping();

// Referencias pendientes que pasaron su plazo.
Schedule::command('referencias:vencer')
    ->dailyAt('01:00')
    ->withoutOverlapping();
