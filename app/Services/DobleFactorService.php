<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Doble factor por aplicación de autenticación (TOTP, RFC 6238).
 *
 * Funciona con Google Authenticator, Authy, 1Password, Bitwarden y cualquier
 * otra: el estándar es el mismo. No hace falta ningún servicio externo ni
 * mandar SMS.
 */
class DobleFactorService
{
    private const CANTIDAD_CODIGOS_RECUPERACION = 8;

    public function __construct(private Google2FA $google2fa) {}

    /**
     * Arranca el alta: genera el secreto y los códigos de recuperación, pero
     * todavía no deja el doble factor activo.
     *
     * Queda pendiente de confirmación a propósito. Si se activara acá, alguien
     * que escanea mal el QR se queda afuera del sistema sin manera de volver.
     *
     * @return list<string> los códigos de recuperación, en claro y por única vez
     */
    public function iniciarAlta(User $usuario): array
    {
        $codigos = $this->generarCodigosDeRecuperacion();

        $usuario->forceFill([
            'two_factor_secret'         => Crypt::encryptString($this->google2fa->generateSecretKey()),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codigos)),
            'two_factor_confirmed_at'   => null,
        ])->save();

        return $codigos;
    }

    /**
     * Confirma el alta contra un código generado por la aplicación del usuario.
     */
    public function confirmar(User $usuario, string $codigo): bool
    {
        if ($usuario->two_factor_secret === null || ! $this->verificarTotp($usuario, $codigo)) {
            return false;
        }

        $usuario->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    public function desactivar(User $usuario): void
    {
        $usuario->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();
    }

    /**
     * Verifica un código de seis dígitos o uno de recuperación.
     *
     * Un código de recuperación se consume al usarse: sirve una sola vez.
     */
    public function verificar(User $usuario, string $codigo): bool
    {
        $codigo = trim($codigo);

        if ($this->verificarTotp($usuario, $codigo)) {
            return true;
        }

        return $this->consumirCodigoDeRecuperacion($usuario, $codigo);
    }

    private function verificarTotp(User $usuario, string $codigo): bool
    {
        if ($usuario->two_factor_secret === null) {
            return false;
        }

        // La ventana de 1 tolera un paso de 30 segundos para arriba y para
        // abajo: los relojes del teléfono y del servidor nunca están exactos.
        return $this->google2fa->verifyKey($this->secretoDe($usuario), $codigo, 1);
    }

    private function consumirCodigoDeRecuperacion(User $usuario, string $codigo): bool
    {
        $codigos = $this->codigosDeRecuperacion($usuario);
        $restantes = array_values(array_filter($codigos, fn (string $c) => ! hash_equals($c, $codigo)));

        if (count($restantes) === count($codigos)) {
            return false;
        }

        $usuario->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($restantes)),
        ])->save();

        return true;
    }

    /** @return list<string> */
    public function codigosDeRecuperacion(User $usuario): array
    {
        if ($usuario->two_factor_recovery_codes === null) {
            return [];
        }

        return json_decode(Crypt::decryptString($usuario->two_factor_recovery_codes), true) ?: [];
    }

    /** @return list<string> */
    public function regenerarCodigosDeRecuperacion(User $usuario): array
    {
        $codigos = $this->generarCodigosDeRecuperacion();

        $usuario->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($codigos)),
        ])->save();

        return $codigos;
    }

    /** El QR que escanea la aplicación del usuario, como SVG embebible. */
    public function qrSvg(User $usuario): string
    {
        $url = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $usuario->email,
            $this->secretoDe($usuario)
        );

        $writer = new Writer(new ImageRenderer(new RendererStyle(192, 0), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    /** Para cargarlo a mano cuando no se puede escanear el QR. */
    public function secretoDe(User $usuario): string
    {
        return Crypt::decryptString($usuario->two_factor_secret);
    }

    /** @return list<string> */
    private function generarCodigosDeRecuperacion(): array
    {
        return collect(range(1, self::CANTIDAD_CODIGOS_RECUPERACION))
            ->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))
            ->all();
    }
}
