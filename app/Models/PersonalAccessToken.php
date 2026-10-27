<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use MongoDB\Laravel\Eloquent\DocumentModel;

/**
 * Token de API de Sanctum guardado en MongoDB (se activa en AppServiceProvider).
 *
 * Tiene que seguir siendo un PersonalAccessToken de Sanctum (NewAccessToken lo exige),
 * así que no hereda de ModeloMongo sino que usa el trait DocumentModel del paquete.
 * Conserva su ObjectId: el id solo viaja dentro del texto del token ("<id>|<secreto>"),
 * no en rutas ni formularios, así que la decisión D1 (ids numéricos) no le hace falta.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use DocumentModel;

    protected $connection = 'mongodb';

    protected $primaryKey = '_id';

    protected $keyType = 'string';
}
