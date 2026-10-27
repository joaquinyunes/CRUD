<?php

namespace App\Support\Mongo;

use Illuminate\Support\Facades\DB;
use MongoDB\Collection;
use MongoDB\Driver\Session;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * Saldo de un producto en un depósito: un documento por par, con `_id`
 * "producto:deposito" (único por construcción, sirve de upsert).
 *
 * Reemplaza al lockForUpdate de SQL: descontar() es UN findOneAndUpdate que
 * filtra por "cantidad >= pedido" y resta con $inc, así que dos ventas que
 * llegan a la vez no pueden dejar el saldo en negativo.
 */
class StockDeposito
{
    public static function coleccion(): Collection
    {
        return DB::connection('mongodb')->getDatabase()->selectCollection('stock_deposito');
    }

    private static function clave(int $productoId, int $depositoId): string
    {
        return "{$productoId}:{$depositoId}";
    }

    /** @return array{session?: Session} */
    private static function sesion(): array
    {
        $sesion = DB::connection('mongodb')->getSession();

        return $sesion ? ['session' => $sesion] : [];
    }

    public static function cantidad(int $productoId, int $depositoId): int
    {
        $fila = self::coleccion()->findOne(['_id' => self::clave($productoId, $depositoId)], self::sesion());

        return (int) ($fila['cantidad'] ?? 0);
    }

    public static function fijar(int $productoId, int $depositoId, int $cantidad): void
    {
        self::coleccion()->updateOne(
            ['_id' => self::clave($productoId, $depositoId)],
            ['$set'   => ['producto_id' => $productoId, 'deposito_id' => $depositoId, 'cantidad' => $cantidad]],
            ['upsert' => true] + self::sesion()
        );
    }

    /**
     * Suma (o resta, con $delta negativo) de forma atómica y devuelve el saldo
     * nuevo. Con $minimo, solo aplica si el saldo no queda por debajo de él;
     * si no se pudo, devuelve null y no toca nada.
     */
    public static function sumar(int $productoId, int $depositoId, int $delta, ?int $minimo = null): ?int
    {
        $filtro = ['_id' => self::clave($productoId, $depositoId)];

        if ($minimo !== null) {
            // "cantidad - pedido >= minimo": no hay fila → no hay stock → no coincide.
            $filtro['cantidad'] = ['$gte' => $minimo - $delta];
        }

        $fila = self::coleccion()->findOneAndUpdate(
            $filtro,
            ['$inc'   => ['cantidad' => $delta], '$setOnInsert' => ['producto_id' => $productoId, 'deposito_id' => $depositoId]],
            ['upsert' => $minimo === null, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER] + self::sesion()
        );

        return $fila === null ? null : (int) $fila['cantidad'];
    }

    /** Fija el saldo en $cantidad y devuelve el que había (en una sola operación atómica). */
    public static function reemplazar(int $productoId, int $depositoId, int $cantidad): int
    {
        $antes = self::coleccion()->findOneAndUpdate(
            ['_id' => self::clave($productoId, $depositoId)],
            ['$set'   => ['producto_id' => $productoId, 'deposito_id' => $depositoId, 'cantidad' => $cantidad]],
            ['upsert' => true, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_BEFORE] + self::sesion()
        );

        return (int) ($antes['cantidad'] ?? 0);
    }
}
