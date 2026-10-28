<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $titulo
 * @property mixed $descripcion
 * @property mixed $prioridad
 * @property mixed $estado
 * @property Carbon|null $fecha_limite
 * @property mixed $asignada_a
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tarea extends ModeloMongo
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'prioridad',
        'estado',
        'fecha_limite',
        'asignada_a',
        'user_id',
    ];

    protected $casts = [
        'fecha_limite' => 'date',
    ];

    /** @return BelongsTo<User, $this> */
    public function asignada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignada_a');
    }

    /** @return BelongsTo<User, $this> */
    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeParaEstado($query, ?string $estado)
    {
        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query;
    }

    public function scopeParaPrioridad($query, ?string $prioridad)
    {
        if ($prioridad) {
            $query->where('prioridad', $prioridad);
        }

        return $query;
    }

    public function scopeParaUsuario($query, ?int $userId)
    {
        if ($userId) {
            $query->where('asignada_a', $userId);
        }

        return $query;
    }

    public function getEstaVencidaAttribute(): bool
    {
        return $this->fecha_limite && $this->fecha_limite->isPast() && $this->estado !== 'completada';
    }
}
