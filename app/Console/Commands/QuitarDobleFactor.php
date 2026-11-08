<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DobleFactorService;
use Illuminate\Console\Command;

/**
 * Rescate: el administrador perdió el teléfono y los códigos de recuperación.
 * Solo se corre desde el servidor (quien tiene acceso a la consola ya manda en la base).
 */
class QuitarDobleFactor extends Command
{
    protected $signature = 'usuario:quitar-doble-factor {email : Correo del usuario}';

    protected $description = 'Desactiva el doble factor de un usuario que perdió su dispositivo y sus códigos.';

    public function handle(DobleFactorService $doble): int
    {
        $usuario = User::where('email', (string) $this->argument('email'))->first();

        if ($usuario === null) {
            $this->error('No existe ese usuario.');

            return self::FAILURE;
        }

        $doble->desactivar($usuario);
        $this->info("Doble factor desactivado para {$usuario->email}. En su próximo ingreso tendrá que volver a activarlo.");

        return self::SUCCESS;
    }
}
