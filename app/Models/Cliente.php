<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MongoDB\Laravel\Eloquent\Model;

class Cliente extends Model
{
    use Auditable, HasFactory;

    protected $connection = 'mongodb';

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'documento',
        'email',
        'telefono',
        'direccion',
        'observaciones',
        'estado',
        'limite_credito',
        'lista_precio_id',
    ];

    protected $casts = [
        'estado' => 'string',
        'limite_credito' => 'decimal:2',
    ];

    /** Mismo problema que ventas(): se arma a mano para no heredar la conexion mongodb. */
    public function listaPrecio(): BelongsTo
    {
        return new BelongsTo(ListaPrecio::query(), $this, 'lista_precio_id', 'id', 'listaPrecio');
    }

    /**
     * OJO: HybridRelations::hasMany() delega a parent::hasMany() para
     * relaciones Mongo -> SQL, y ese metodo (core de Eloquent) copia la
     * conexion del padre ('mongodb') al modelo relacionado via
     * newRelatedInstance() -> rompe la query contra "ventas" (usa la
     * conexion mongodb en vez de la propia de Venta). Se arma a mano.
     */
    public function ventas(): HasMany
    {
        return new HasMany(Venta::query(), $this, 'cliente_id', '_id');
    }

    /**
     * Deuda actual: ventas no anuladas menos lo pagado y lo devuelto.
     */
    public function saldo(): float
    {
        return (float) $this->ventas()
            ->whereIn('estado', ['completada', 'pendiente'])
            ->get()
            ->sum(fn (Venta $v) => $v->saldoPendiente());
    }

    public function creditoDisponible(): float
    {
        return round((float) $this->limite_credito - $this->saldo(), 2);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', 'activo');
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        if (! $texto) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto) {
            $q->where('nombre', 'like', "%{$texto}%")
                ->orWhere('apellido', 'like', "%{$texto}%")
                ->orWhere('documento', 'like', "%{$texto}%");
        });
    }

    public function nombreCompleto(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
