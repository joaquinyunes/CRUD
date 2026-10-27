<?php

namespace App\Console\Commands;

use App\Support\Mongo\Indices;
use Illuminate\Console\Command;

class CrearIndicesMongo extends Command
{
    protected $signature = 'mongo:indices';

    protected $description = 'Crea (o verifica) los índices de MongoDB declarados en database/mongo/indices.php';

    public function handle(): int
    {
        $this->info(Indices::crear().' índices verificados.');

        return self::SUCCESS;
    }
}
