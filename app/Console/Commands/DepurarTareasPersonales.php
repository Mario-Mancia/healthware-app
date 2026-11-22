<?php

namespace App\Console\Commands;

use App\Models\TareaPersonal;
use App\Services\RecordatorioTareasService;
use Illuminate\Console\Command;

class DepurarTareasPersonales extends Command
{
    protected $signature = 'tareas:depurar';

    protected $description = 'Envía a la papelera las tareas vencidas y elimina de verdad las que cumplieron 30 días en ella';

    public function handle(RecordatorioTareasService $recordatorios): int
    {
        // Primero los avisos pendientes: así ninguna tarea vencida llega a la
        // papelera sin haber notificado su vencimiento.
        $recordatorios->procesar();

        $resultado = TareaPersonal::depurar();

        $this->info(
            'Tareas enviadas a la papelera: ' . $resultado['enviadas']
            . ' · Tareas eliminadas definitivamente: ' . $resultado['purgadas']
        );

        return self::SUCCESS;
    }
}
