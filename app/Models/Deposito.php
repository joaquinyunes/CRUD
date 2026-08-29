<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposito extends Model
{
    protected $table = 'depositos';

    protected $fillable = ['nombre', 'direccion', 'es_principal', 'activo'];

    protected $casts = [
        'es_principal' => 'boolean',
        'activo'       => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public static function principalId(): int
    {
        return (int) (static::where('es_principal', true)->value('id')
            ?? static::orderBy('id')->value('id'));
    }
}
