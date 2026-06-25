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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }

    public function modulo(): string
    {
        return explode('.', $this->clave)[0] ?? $this->clave;
    }

    public function accion(): string
    {
        return explode('.', $this->clave)[1] ?? '';
    }

    public static function agrupadosPorModulo($permisos)
    {
        return $permisos->groupBy(fn(self $p) => $p->modulo());
    }
}
