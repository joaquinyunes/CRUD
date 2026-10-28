<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $nombre
 * @property mixed $ruta
 * @property mixed $tipo
 * @property int $tamano
 * @property mixed $relacionado_tipo
 * @property mixed $relacionado_id
 * @property mixed $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Archivo extends ModeloMongo
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
        if (Storage::disk(self::discoNuevo())->exists($this->ruta)) {
            return self::discoNuevo();
        }

        return 'public';
    }

    /**
     * Donde se guardan los archivos nuevos: el disco por defecto (FILESYSTEM_DISK),
     * que es privado —local o un bucket S3—, nunca el que se sirve por URL directa.
     */
    public static function discoNuevo(): string
    {
        $disco = (string) config('filesystems.default');

        return $disco === 'public' ? 'local' : $disco;
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
