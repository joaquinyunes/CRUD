<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use Auditable;

    protected $table = 'configuracion';

    protected $fillable = ['clave', 'valor', 'grupo'];

    private const TTL = 3600;

    /**
     * Devuelve el valor guardado, o el default si la clave todavía no existe.
     *
     * Solo se cachea lo que está en la base. Cachear el default traía dos
     * problemas: dos llamadas con defaults distintos para la misma clave se
     * pisaban entre sí durante una hora, y una configuración cargada después
     * del primer arranque no se veía hasta que la caché expiraba, porque
     * establecer() nunca se había ejecutado y no había nada que invalidar.
     */
    public static function obtener(string $clave, ?string $default = null): ?string
    {
        $valor = Cache::get(self::cacheKey($clave));

        if ($valor !== null) {
            return $valor;
        }

        $valor = static::where('clave', $clave)->value('valor');

        if ($valor === null) {
            return $default;
        }

        Cache::put(self::cacheKey($clave), $valor, self::TTL);

        return $valor;
    }

    public static function establecer(string $clave, ?string $valor, string $grupo = 'general'): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor, 'grupo' => $grupo]);

        Cache::forget(self::cacheKey($clave));
    }

    /** @return array<string,string> */
    public static function obtenerGrupo(string $grupo): array
    {
        return static::where('grupo', $grupo)->pluck('valor', 'clave')->toArray();
    }

    private static function cacheKey(string $clave): string
    {
        return "setting_{$clave}";
    }
}
