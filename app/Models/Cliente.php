<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read float $saldo_actual
 */
class Cliente extends Model
{
    use Auditable, HasFactory;

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
    ];

    protected $casts = [
        'estado'         => 'string',
        'limite_credito' => 'decimal:2',
    ];

    /** @return HasMany<Venta, $this> */
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Deuda actual: ventas no anuladas menos lo pagado y lo devuelto.
     */
    /** Como se nombra al cliente en los selectores y comprobantes. */
    public function etiquetaCorta(): string
    {
        return trim($this->apellido.', '.$this->nombre, ', ');
    }

    public function saldo(): float
    {
        return (float) $this->ventas()
            ->whereIn('estado', ['completada', 'pendiente'])
            ->get()
            ->sum(fn (Venta $v) => $v->saldoPendiente());
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
