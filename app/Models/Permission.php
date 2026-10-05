<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'clave',
    ];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
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
