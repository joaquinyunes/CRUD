<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $nombre
 * @property mixed $direccion
 * @property bool $es_principal
 * @property bool $activo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Deposito extends ModeloMongo
{
    use Auditable;

    protected $table = 'depositos';

    protected $fillable = ['nombre', 'direccion', 'es_principal', 'activo'];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo'       => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public static function principalId(): int
    {
        return (int) (static::where('es_principal', true)->value('_id')
            ?? static::orderBy('_id')->value('_id'));
    }
}
