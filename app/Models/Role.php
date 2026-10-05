<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    use Auditable;

    public $timestamps = false;

    protected $fillable = [
        'nombre',
    ];

    const ADMINISTRADOR = 'Administrador';

    const SUPERVISOR = 'Supervisor';

    const EMPLEADO = 'Empleado';

    const CLIENTE = 'Cliente';

    const SOLO_LECTURA = 'Solo Lectura';

    const VENDEDOR = 'Vendedor';

    /**
     * Permiso comodín: quien lo tiene puede todo.
     *
     * Existe para que "administrador" sea un rol como cualquier otro y no un
     * nombre especial comparado por string dentro del middleware.
     */
    const TODO = '*';

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function tienePermiso(string $clave): bool
    {
        $claves = self::clavesDe($this->id);

        return in_array(self::TODO, $claves, true) || in_array($clave, $claves, true);
    }

    public function sincronizarPermisos(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);

        self::olvidarClaves($this->id);
    }

    /**
     * Las claves de permiso de un rol, cacheadas.
     *
     * El middleware corre en cada request; sin esto, cada pageview hacía dos
     * consultas (el rol y sus permisos) para no descubrir nada nuevo.
     *
     * @return list<string>
     */
    public static function clavesDe(int $roleId): array
    {
        return Cache::remember(
            self::cacheKey($roleId),
            3600,
            fn () => self::find($roleId)?->permissions()->pluck('clave')->all() ?? []
        );
    }

    public static function olvidarClaves(int $roleId): void
    {
        Cache::forget(self::cacheKey($roleId));
    }

    protected static function booted(): void
    {
        // Cambiar o borrar un rol tiene que verse en el acto, no en una hora.
        static::saved(fn (self $rol) => self::olvidarClaves($rol->id));
        static::deleted(fn (self $rol) => self::olvidarClaves($rol->id));
    }

    private static function cacheKey(int $roleId): string
    {
        return "rol_permisos_{$roleId}";
    }
}
