<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Expresiones SQL de agrupación por período, independientes del motor.
 *
 * Los reportes agrupan ventas y compras por día, semana y mes. Cada motor
 * escribe esas funciones distinto (MySQL usa YEARWEEK/DATE_FORMAT, PostgreSQL
 * usa TO_CHAR, SQLite usa STRFTIME), así que el dialecto se resuelve acá y los
 * controllers piden el período por nombre.
 */
class PeriodoSql
{
    /** Día calendario, formato YYYY-MM-DD. */
    public static function dia(string $columna = 'fecha'): string
    {
        return match (self::driver()) {
            'pgsql'  => "TO_CHAR($columna, 'YYYY-MM-DD')",
            'sqlite' => "STRFTIME('%Y-%m-%d', $columna)",
            default  => "DATE($columna)",
        };
    }

    /** Semana ISO ordenable, formato YYYY-WW. */
    public static function semana(string $columna = 'fecha'): string
    {
        return match (self::driver()) {
            'pgsql'  => "TO_CHAR($columna, 'IYYY-IW')",
            'sqlite' => "STRFTIME('%Y-%W', $columna)",
            default  => "DATE_FORMAT($columna, '%x-%v')",
        };
    }

    /** Mes calendario, formato YYYY-MM. */
    public static function mes(string $columna = 'fecha'): string
    {
        return match (self::driver()) {
            'pgsql'  => "TO_CHAR($columna, 'YYYY-MM')",
            'sqlite' => "STRFTIME('%Y-%m', $columna)",
            default  => "DATE_FORMAT($columna, '%Y-%m')",
        };
    }

    /** Devuelve la expresión del período pedido: diario, semanal o mensual. */
    public static function para(string $periodo, string $columna = 'fecha'): string
    {
        return match ($periodo) {
            'semanal' => self::semana($columna),
            'mensual' => self::mes($columna),
            default   => self::dia($columna),
        };
    }

    protected static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
