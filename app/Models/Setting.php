<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'configuracion';
    protected $fillable = ['clave', 'valor', 'grupo'];

    public static function obtener(string $clave, ?string $default = null): ?string
    {
        return Cache::remember("setting_{$clave}", 3600, function () use ($clave, $default) {
            $setting = static::where('clave', $clave)->first();
            return $setting ? $setting->valor : $default;
        });
    }

    public static function establecer(string $clave, ?string $valor, string $grupo = 'general'): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor, 'grupo' => $grupo]);
        Cache::forget("setting_{$clave}");
    }

    public static function obtenerGrupo(string $grupo): array
    {
        return static::where('grupo', $grupo)->pluck('valor', 'clave')->toArray();
    }
}
