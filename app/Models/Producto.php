<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Producto extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'codigo',
        'codigo_barra',
        'nombre',
        'descripcion',
        'categoria_id',
        'marca',
        'precio_compra',
        'precio_venta',
        'iva_alicuota',
        'stock',
        'stock_minimo',
        'punto_pedido',
        'unidad_medida_id',
        'es_pesable',
        'imagen',
        'estado',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'iva_alicuota' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'punto_pedido' => 'integer',
        'es_pesable' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Al alta, el stock inicial vive en el depósito principal.
        static::created(function (Producto $producto) {
            if (! Schema::hasTable('depositos')) {
                return;
            }
            $depositoId = Deposito::principalId();
            if (! $depositoId) {
                return;
            }
            \Illuminate\Support\Facades\DB::table('stock_deposito')->updateOrInsert(
                ['producto_id' => $producto->id, 'deposito_id' => $depositoId],
                ['cantidad' => (int) $producto->stock],
            );
        });
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', '!=', 'eliminado');
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where(function ($q) use ($codigo) {
            $q->where('codigo', $codigo)
                ->orWhere('codigo_barra', $codigo)
                ->orWhereHas('codigos', fn ($c) => $c->where('codigo', $codigo));
        });
    }

    public function codigos(): HasMany
    {
        return $this->hasMany(ProductoCodigo::class);
    }

    public function preciosLista(): HasMany
    {
        return $this->hasMany(PrecioProducto::class);
    }

    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class);
    }

    public function scopeStockCritico($query)
    {
        return $query->where('stock', '<=', \DB::raw('stock_minimo'))
            ->where('estado', 'activo');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function depositos(): BelongsToMany
    {
        return $this->belongsToMany(Deposito::class, 'stock_deposito')
            ->withPivot('cantidad');
    }

    public function stockEn(int $depositoId): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('stock_deposito')
            ->where('producto_id', $this->id)
            ->where('deposito_id', $depositoId)
            ->value('cantidad');
    }

    public function getImagenUrlAttribute(): string
    {
        if ($this->imagen) {
            return asset('storage/'.$this->imagen);
        }

        return asset('images/producto-placeholder.png');
    }

    public function estaEnStockCritico(): bool
    {
        return $this->stock <= $this->stock_minimo;
    }
}
