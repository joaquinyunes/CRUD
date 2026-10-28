<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $clave
 */
class Permission extends ModeloMongo
{
    public $timestamps = false;

    protected $fillable = [
        'clave',
    ];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, null, 'permission_ids', 'role_ids');
    }

    public function modulo(): string
    {
        return explode('.', $this->clave)[0];
    }

    public function accion(): string
    {
        return explode('.', $this->clave, 2)[1] ?? '';
    }

    public static function agrupadosPorModulo($permisos)
    {
        return $permisos->groupBy(fn (self $p) => $p->modulo());
    }
}
