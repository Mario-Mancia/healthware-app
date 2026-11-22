<?php

namespace App\Console\Commands;

use App\Services\ResumenReferenciasService;
use Illuminate\Console\Command;

class ResumirReferenciasRecepcion extends Command
{
    protected $signature = 'referencias:resumen-recepcion';

    protected $description = 'Avisa a recepción, en una sola notificación diaria, de las referencias pendientes de programar';

    public function handle(ResumenReferenciasService $servicio): int
    {
        $resultado = $servicio->enviar();

        $this->info(
            'Referencias pendientes: ' . $resultado['pendientes']
            . ' · Recepcionistas avisados: ' . $resultado['destinatarios']
        );

        return self::SUCCESS;
    }
}
