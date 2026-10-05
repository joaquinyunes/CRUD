<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Archivo extends Model
{
    use HasFactory;

    /** Donde se guardan los archivos nuevos: un disco que no se sirve por URL. */
    public const DISCO = 'local';

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

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * El disco donde está este archivo. Los que se subieron antes de que
     * existiera el disco privado quedaron en 'public' y se siguen encontrando.
     */
    public function disco(): string
    {
        if (Storage::disk(self::DISCO)->exists($this->ruta)) {
            return self::DISCO;
        }

        return 'public';
    }

    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->tamano;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
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
