<?php

namespace App\Console\Commands;

use App\Models\Notificacion;
use Illuminate\Console\Command;

class PurgarNotificaciones extends Command
{
    protected $signature = 'notificaciones:purgar';

    protected $description = 'Elimina las notificaciones que cumplieron 30 días, leídas o no';

    public function handle(): int
    {
        $eliminadas = Notificacion::purgarAntiguas();

        $this->info('Notificaciones eliminadas: ' . $eliminadas);

        return self::SUCCESS;
    }
}
