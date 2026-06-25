<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    const ADMINISTRADOR = 'Administrador';
    const SUPERVISOR    = 'Supervisor';
    const EMPLEADO      = 'Empleado';
    const CLIENTE       = 'Cliente';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function tienePermiso(string $clave): bool
    {
        return $this->permissions->contains('clave', $clave);
    }

    public function sincronizarPermisos(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }
}
