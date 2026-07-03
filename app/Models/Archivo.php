<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Archivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ruta',
        'tipo',
        'tamano',
        'relacionado_tipo',
        'relacionado_id',
        'user_id',
    ];

    protected $casts = [
        'tamano' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->tamano;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function getEsImagenAttribute(): bool
    {
        return in_array($this->tipo, ['image/jpeg', 'image/png', 'image/webp']);
    }

    public function getEsPdfAttribute(): bool
    {
        return $this->tipo === 'application/pdf';
    }

    public function scopeParaModelo($query, ?string $tipo, ?int $id)
    {
        if ($tipo && $id) {
            $query->where('relacionado_tipo', $tipo)
                  ->where('relacionado_id', $id);
        }

        return $query;
    }
}
