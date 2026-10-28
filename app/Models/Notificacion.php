<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $titulo
 * @property mixed $mensaje
 * @property mixed $tipo
 * @property mixed $url
 * @property bool $leida
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Notificacion extends ModeloMongo
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

    /** @return BelongsTo<User, $this> */
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
