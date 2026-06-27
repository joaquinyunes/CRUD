<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
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
