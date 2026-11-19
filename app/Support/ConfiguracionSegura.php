<?php

namespace App\Support;

/**
 * Configuraciones que en producción son un agujero y no una preferencia.
 */
class ConfiguracionSegura
{
    /**
     * @return list<string> problemas graves: la aplicación no debe arrancar así
     */
    public static function errores(bool $esProduccion, string $disco): array
    {
        $errores = [];

        // El disco «public» publica en la web lo que se guarda: comprobantes, adjuntos, respaldos.
        if ($esProduccion && $disco === 'public') {
            $errores[] = 'FILESYSTEM_DISK=public no está permitido en producción: los archivos y los respaldos quedarían públicos. Usá «local» o «s3».';
        }

        return $errores;
    }

    /**
     * @return list<string> avisos: se registran pero no frenan el arranque
     */
    public static function avisos(bool $esProduccion, string $mailer): array
    {
        $avisos = [];

        // Con MAIL_MAILER=log los enlaces de «olvidé mi contraseña» (con su token) terminan en los logs.
        if ($esProduccion && in_array($mailer, ['log', 'array'], true)) {
            $avisos[] = 'MAIL_MAILER='.$mailer.' en producción: los correos (incluido el enlace de restablecer contraseña) no se envían y quedan en los logs. Configurá un SMTP.';
        }

        return $avisos;
    }
}
