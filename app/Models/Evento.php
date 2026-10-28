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
 * @property mixed $color
 * @property Carbon|null $inicio
 * @property Carbon|null $fin
 * @property bool $todo_el_dia
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Evento extends ModeloMongo
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'color',
        'inicio',
        'fin',
        'todo_el_dia',
        'user_id',
    ];

    protected $casts = [
        'inicio'      => 'datetime',
        'fin'         => 'datetime',
        'todo_el_dia' => 'boolean',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStartAttribute(): string
    {
        if ($this->todo_el_dia) {
            return $this->inicio->format('Y-m-d');
        }

        return $this->inicio->format('Y-m-d\TH:i:s');
    }

    public function getEndAttribute(): ?string
    {
        if (! $this->fin) {
            return null;
        }

        if ($this->todo_el_dia) {
            return $this->fin->format('Y-m-d');
        }

        return $this->fin->format('Y-m-d\TH:i:s');
    }
}
