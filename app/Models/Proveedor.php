<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read float $saldo_actual
 * @property int $id
 * @property int $_id
 * @property mixed $nombre
 * @property mixed $cuit
 * @property mixed $telefono
 * @property mixed $email
 * @property mixed $direccion
 * @property mixed $estado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Proveedor extends ModeloMongo
{
    use Auditable, HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'cuit',
        'telefono',
        'email',
        'direccion',
        'estado',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /** @return HasMany<Compra, $this> */
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

    /**
     * El saldo como atributo, para las vistas de cuenta corriente.
     *
     * Antes el controller se lo asignaba al modelo a mano: eso ensuciaba el
     * registro con una columna que no existe, y un save() posterior habría
     * intentado escribirla.
     */
    protected function saldoActual(): Attribute
    {
        return Attribute::get(fn (): float => $this->saldo());
    }

    public function scopeBuscar($query, ?string $buscar)
    {
        if (! $buscar) {
            return $query;
        }

        return $query->where('nombre', 'like', "%{$buscar}%")
            ->orWhere('cuit', 'like', "%{$buscar}%")
            ->orWhere('email', 'like', "%{$buscar}%");
    }
}
