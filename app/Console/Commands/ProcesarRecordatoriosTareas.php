<?php

namespace App\Console\Commands;

use App\Services\RecordatorioTareasService;
use Illuminate\Console\Command;

class ProcesarRecordatoriosTareas extends Command
{
    protected $signature = 'tareas:recordatorios';

    protected $description = 'Genera las notificaciones de recordatorio y vencimiento de las tareas personales';

    public function handle(RecordatorioTareasService $servicio): int
    {
        $resultado = $servicio->procesar();

        $this->info(
            'Recordatorios enviados: ' . $resultado['recordatorios']
            . ' · Avisos de vencimiento: ' . $resultado['vencidas']
        );

        return self::SUCCESS;
    }
}
