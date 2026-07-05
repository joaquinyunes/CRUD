<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    use HasFactory;

    protected $fillable = [
        'titulo',
        'mensaje',
        'tipo',
        'url',
        'leida',
        'user_id',
    ];

    protected $casts = [
        'leida' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    public function scopeParaUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
