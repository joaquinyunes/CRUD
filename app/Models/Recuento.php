<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recuento extends Model
{
    protected $table = 'recuentos';

    protected $fillable = ['numero', 'deposito_id', 'estado', 'observaciones', 'user_id', 'aplicado_en'];

    protected $casts = ['aplicado_en' => 'datetime'];

    public function deposito(): BelongsTo
    {
        return $this->belongsTo(Deposito::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RecuentoDetalle::class);
    }

    public function contados(): int
    {
        return $this->detalles->whereNotNull('contado')->count();
    }

    public function conDiferencia(): int
    {
        return $this->detalles->where('diferencia', '!=', 0)->count();
    }
}
