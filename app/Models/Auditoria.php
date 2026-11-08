<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $user_id
 * @property mixed $ip
 * @property mixed $accion
 * @property mixed $modelo_afectado
 * @property mixed $modelo_id
 * @property array|null $valor_anterior
 * @property array|null $valor_nuevo
 * @property Carbon|null $created_at
 */
class Auditoria extends ModeloMongo
{
    public $timestamps = false;

    protected $table = 'auditoria';

    protected $fillable = [
        'user_id',
        'ip',
        'accion',
        'modelo_afectado',
        'modelo_id',
        'valor_anterior',
        'valor_nuevo',
        'created_at',
    ];

    protected $casts = [
        'valor_anterior' => 'json',
        'valor_nuevo'    => 'json',
        'created_at'     => 'datetime',
    ];

    /**
     * El registro de auditoría es de solo agregar: ni un administrador puede
     * reescribir ni borrar una línea desde la aplicación. (En la base, el usuario
     * de Atlas de la app debería tener además un rol sin `update`/`remove` sobre
     * esta colección; ver docs/SEGURIDAD.md.)
     */
    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('La auditoría es inmutable: no se puede modificar.'));
        static::deleting(fn () => throw new \LogicException('La auditoría es inmutable: no se puede borrar.'));
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeParaBuscar(Builder $query, ?string $texto): Builder
    {
        if (blank($texto)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto) {
            $q->where('accion', 'like', "%{$texto}%")
                ->orWhere('modelo_afectado', 'like', "%{$texto}%");
        });
    }

    public function scopeParaModelo(Builder $query, ?string $modelo): Builder
    {
        if (blank($modelo)) {
            return $query;
        }

        return $query->where('modelo_afectado', $modelo);
    }

    public function scopeParaUsuario(Builder $query, ?int $userId): Builder
    {
        if (blank($userId)) {
            return $query;
        }

        return $query->where('user_id', $userId);
    }
}
