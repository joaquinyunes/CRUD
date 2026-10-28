<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Mongo\Transaccion;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UniversalSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Deja lista una instalación real: permisos, rol de administrador, datos base y
 * la primera cuenta.
 *
 * Con DB_SEED_ON_BOOT=false el entrypoint no corre el DatabaseSeeder, y eso es
 * lo correcto —trae cuentas de demostración con contraseñas conocidas—, pero
 * dejaba una base sin un solo permiso, rol ni usuario: nadie podía entrar.
 *
 * Es idempotente y corre en cada arranque: sincroniza el catálogo de permisos y
 * se los da al administrador (así un permiso nuevo llega solo), pero nunca toca
 * la contraseña de una cuenta que ya existe ni los permisos de los demás roles.
 */
class InstalarSistema extends Command
{
    protected $signature = 'sistema:instalar
                            {--nombre= : Nombre del administrador (o ADMIN_NAME)}
                            {--email= : Correo del administrador (o ADMIN_EMAIL)}
                            {--password= : Contraseña (o ADMIN_PASSWORD); si falta y hay terminal, se pide}';

    protected $description = 'Prepara una instalación real: permisos, rol Administrador, datos base y la primera cuenta.';

    public function handle(): int
    {
        Transaccion::en(function () {
            $this->callSilent('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);
            $this->callSilent('db:seed', ['--class' => UniversalSeeder::class, '--force' => true]);

            $admin = Role::firstOrCreate(['nombre' => Role::ADMINISTRADOR]);
            $admin->sincronizarPermisos(Permission::pluck('id')->all());
        });

        $this->info('Permisos, rol Administrador y datos base al día.');

        return $this->crearAdministrador();
    }

    private function crearAdministrador(): int
    {
        $email = $this->option('email') ?: config('sistema.admin.email');

        if (! $email) {
            if (User::query()->exists()) {
                return self::SUCCESS;
            }

            if (! $this->input->isInteractive()) {
                $this->warn('No hay ningún usuario y falta ADMIN_EMAIL / ADMIN_PASSWORD: nadie va a poder entrar.');

                return self::SUCCESS;
            }

            $email = $this->ask('Correo del administrador');
        }

        if (User::where('email', $email)->exists()) {
            $this->line("La cuenta {$email} ya existe; no se toca.");

            return self::SUCCESS;
        }

        $password = $this->option('password') ?: config('sistema.admin.password');

        if (! $password && $this->input->isInteractive()) {
            $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        }

        $datos = ['email' => $email, 'password' => $password];
        $validador = Validator::make($datos, [
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(12)],
        ], [
            'password.required' => 'Falta la contraseña del administrador (ADMIN_PASSWORD).',
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        // forceFill: email_verified_at no es asignable en masa, y sin verificar
        // el administrador chocaría con la pantalla de "verificá tu correo".
        (new User)->forceFill([
            'name'              => $this->option('nombre') ?: config('sistema.admin.nombre'),
            'email'             => $email,
            'password'          => Hash::make($password),
            'role_id'           => Role::where('nombre', Role::ADMINISTRADOR)->value('id'),
            'email_verified_at' => now(),
        ])->save();

        $this->info("Administrador {$email} creado.");

        return self::SUCCESS;
    }
}
