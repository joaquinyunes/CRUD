<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CrearBackup extends Command
{
    protected $signature = 'backup:crear {--conservar=14 : Cuántos respaldos dejar; los más viejos se borran}';

    protected $description = 'Crea un respaldo de la base y borra los más antiguos';

    public function handle(BackupService $backups): int
    {
        $nombre = $backups->crear();
        $borrados = $backups->podar((int) $this->option('conservar'));

        $this->info("Backup creado: {$nombre}".($borrados ? " ({$borrados} antiguos eliminados)" : ''));

        return self::SUCCESS;
    }
}
