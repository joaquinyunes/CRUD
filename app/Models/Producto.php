<?php

namespace App\Models;

use App\Support\Mongo\StockDeposito;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends ModeloMongo
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
        'stock',
        'stock_minimo',
        'unidad_medida_id',
        'imagen',
        'estado',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta'  => 'decimal:2',
        'stock'         => 'integer',
        'stock_minimo'  => 'integer',
    ];

    protected static function booted(): void
    {
        // Al alta, el stock inicial vive en el depósito principal.
        static::created(function (Producto $producto) {
            $depositoId = Deposito::principalId();
            if (! $depositoId) {
                return;
            }
            StockDeposito::fijar($producto->id, $depositoId, (int) $producto->stock);
        });
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', '!=', 'eliminado');
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo)->orWhere('codigo_barra', $codigo);
    }

    public function scopeStockCritico($query)
    {
        return $query->whereRaw(['$expr' => ['$lte' => ['$stock', '$stock_minimo']]])
            ->where('estado', 'activo');
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return BelongsTo<UnidadMedida, $this> */
    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function stockEn(int $depositoId): int
    {
        return StockDeposito::cantidad($this->id, $depositoId);
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
