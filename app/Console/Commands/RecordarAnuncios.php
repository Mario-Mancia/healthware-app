<?php

namespace App\Console\Commands;

use App\Services\RecordatorioAnunciosService;
use Illuminate\Console\Command;

class RecordarAnuncios extends Command
{
    protected $signature = 'anuncios:recordatorios';

    protected $description = 'Envía el recordatorio de los eventos de anuncios que comienzan en 15 minutos o menos';

    public function handle(RecordatorioAnunciosService $recordatorios): int
    {
        $this->info('Recordatorios de anuncios enviados: ' . $recordatorios->procesar());

        return self::SUCCESS;
    }
}
