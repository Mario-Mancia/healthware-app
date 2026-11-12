<?php

namespace App\Console\Commands;

use App\Services\ReferenciaMedicaService;
use Illuminate\Console\Command;

class VencerReferenciasMedicas extends Command
{
    protected $signature = 'referencias:vencer';

    protected $description = 'Marca como vencidas las referencias médicas pendientes que pasaron su plazo';

    public function handle(ReferenciaMedicaService $servicio): int
    {
        $this->info('Referencias vencidas: ' . $servicio->vencerPendientes());

        return self::SUCCESS;
    }
}
