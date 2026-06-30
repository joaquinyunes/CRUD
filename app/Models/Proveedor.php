<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'cuit',
        'telefono',
        'email',
        'direccion',
    ];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function scopeBuscar($query, ?string $buscar)
    {
        if (!$buscar) {
            return $query;
        }

        return $query->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('cuit', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");
    }
}
