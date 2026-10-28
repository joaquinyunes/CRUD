<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $_id
 * @property mixed $nombre
 * @property mixed $descripcion
 * @property bool $estado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Categoria extends ModeloMongo
{
    use Auditable, HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', true);
    }
}
