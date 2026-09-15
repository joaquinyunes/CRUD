<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'cuit',
        'telefono',
        'email',
        'direccion',
    ];

    /**
     * OJO: HybridRelations::hasMany() delega a parent::hasMany() para
     * relaciones Mongo -> SQL, y ese metodo (core de Eloquent) copia la
     * conexion del padre ('mongodb') al modelo relacionado via
     * newRelatedInstance() -> rompe la query contra "compras". Se arma a mano.
     */
    public function compras(): HasMany
    {
        return new HasMany(Compra::query(), $this, 'proveedor_id', '_id');
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
