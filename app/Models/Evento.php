<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
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
        if (!$this->fin) {
            return null;
        }

        if ($this->todo_el_dia) {
            return $this->fin->format('Y-m-d');
        }

        return $this->fin->format('Y-m-d\TH:i:s');
    }
}
