<?php

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use Auditable;

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Un usuario nace activo. Sin esto, el modelo recién creado no trae el
     * valor que puso la base y el sistema lo ve como desactivado.
     */
    protected $attributes = [
        'activo' => true,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'activo'                  => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return HasMany<Venta, $this> */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /** @return HasMany<Compra, $this> */
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    /** @return HasMany<MovimientoStock, $this> */
    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class);
    }

    /** @return HasMany<CajaSesion, $this> */
    public function sesionesCaja(): HasMany
    {
        return $this->hasMany(CajaSesion::class);
    }

    /**
     * True si el usuario ya dejó rastro en el sistema. Un usuario así no se
     * borra: se desactiva, para que sus comprobantes conserven el nombre de
     * quien los hizo.
     */
    public function tieneHistorial(): bool
    {
        return $this->ventas()->exists()
            || $this->compras()->exists()
            || $this->movimientosStock()->exists()
            || $this->sesionesCaja()->exists();
    }

    /**
     * True si quitarle el rol de administrador (o desactivarlo o borrarlo) dejaría
     * al sistema sin ningún usuario activo que pueda administrarlo.
     *
     * "Administrador" es quien tiene el permiso comodín, no un nombre de rol.
     */
    public function dejariaSinAdministrador(?int $nuevoRoleId = null): bool
    {
        $idsAdmin = Role::whereHas('permissions', fn ($q) => $q->where('clave', Role::TODO))->pluck('id');

        if (! $idsAdmin->contains($this->role_id)) {
            return false;                       // no era admin: nada que proteger
        }

        if ($nuevoRoleId !== null && $idsAdmin->contains($nuevoRoleId)) {
            return false;                       // sigue siendo admin
        }

        return static::whereIn('role_id', $idsAdmin)
            ->where('id', '!=', $this->id)
            ->where('activo', true)
            ->doesntExist();
    }

    /** True si el usuario terminó de dar de alta su doble factor. */
    public function tieneDobleFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Los usuarios que pueden todo.
     *
     * Se resuelve por el permiso comodín: un rol que alguien creó y llamó
     * "Dueño" cuenta igual que uno llamado "Administrador".
     */
    public function scopeAdministradores($query)
    {
        return $query->activos()->whereHas(
            'role.permissions',
            fn ($q) => $q->where('clave', Role::TODO)
        );
    }
}
