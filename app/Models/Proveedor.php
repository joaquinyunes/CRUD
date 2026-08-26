<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

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

    /**
     * Deuda a proveedores: compras no anuladas menos lo pagado y lo devuelto.
     */
    public function saldo(): float
    {
        return (float) $this->compras()
            ->whereIn('estado', ['completada', 'pendiente'])
            ->get()
            ->sum(fn (Compra $c) => $c->saldoPendiente());
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
