<?php

namespace App\Support\Mongo;

use Illuminate\Support\Facades\DB;
use MongoDB\Database;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * Secuencias en la colección `contadores`: ids numéricos (D1) y numeración de
 * comprobantes. Todo con findOneAndUpdate + $inc: Mongo lo ejecuta de forma
 * atómica, sin leer primero ni bloquear.
 */
class Contadores
{
    /**
     * Próximo id de una colección. Va FUERA de la transacción de la venta
     * (no usa su sesión): así los ids no son un punto de choque entre ventas
     * simultáneas, y un rollback deja un hueco en los ids, que no importa.
     */
    public static function siguienteId(string $coleccion): int
    {
        return self::incrementar(self::base(), "id:{$coleccion}", 0, null);
    }

    /**
     * Próximo número de una serie de comprobantes. DENTRO de la transacción
     * (usa su sesión): si se revierte, el contador vuelve atrás con ella y la
     * serie no deja huecos; si dos ventas chocan, Mongo aborta una y el
     * transaction() de la conexión la reintenta.
     *
     * @param  int  $desde  último número ya emitido, si la serie recién nace
     */
    public static function siguienteNumero(string $serie, int $desde): int
    {
        $conexion = DB::connection('mongodb');

        return self::incrementar($conexion->getDatabase(), "num:{$serie}", $desde, $conexion->getSession());
    }

    public static function existeNumero(string $serie): bool
    {
        $conexion = DB::connection('mongodb');

        return $conexion->getCollection('contadores')->countDocuments(
            ['_id' => "num:{$serie}"], ['session' => $conexion->getSession()]
        ) > 0;
    }

    private static function base(): Database
    {
        return DB::connection('mongodb')->getDatabase();
    }

    private static function incrementar(Database $base, string $clave, int $desde, $sesion): int
    {
        $opciones = ['session' => $sesion];
        $coleccion = $base->selectCollection('contadores');

        if ($desde > 0) {
            // Solo la primera vez: arranca desde el último número que ya exista.
            $coleccion->updateOne(['_id' => $clave], ['$setOnInsert' => ['ultimo' => $desde]], ['upsert' => true] + $opciones);
        }

        $documento = $coleccion->findOneAndUpdate(
            ['_id' => $clave],
            ['$inc'   => ['ultimo' => 1]],
            ['upsert' => true, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER] + $opciones
        );

        return (int) $documento['ultimo'];
    }
}
