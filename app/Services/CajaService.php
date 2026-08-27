<?php

namespace App\Services;

use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\User;
use RuntimeException;

class CajaService
{
    public function sesionAbierta(?int $userId = null): ?CajaSesion
    {
        return CajaSesion::abierta()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('abierta_en')
            ->first();
    }

    public function abrir(User $user, float $montoInicial, ?string $observaciones = null): CajaSesion
    {
        if ($this->sesionAbierta($user->id)) {
            throw new RuntimeException('Ya tenés una caja abierta. Cerrala antes de abrir otra.');
        }

        return CajaSesion::create([
            'user_id'       => $user->id,
            'monto_inicial' => round($montoInicial, 2),
            'estado'        => 'abierta',
            'observaciones' => $observaciones,
            'abierta_en'    => now(),
        ]);
    }

    public function registrarMovimiento(
        string $tipo,
        string $concepto,
        float $monto,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?int $userId = null
    ): ?CajaMovimiento {
        $userId ??= auth()->id();
        $sesion = $this->sesionAbierta($userId) ?? $this->sesionAbierta();

        if (! $sesion || $monto <= 0) {
            return null;
        }

        return $sesion->movimientos()->create([
            'tipo'            => $tipo,
            'concepto'        => $concepto,
            'monto'           => round($monto, 2),
            'referencia_tipo' => $referenciaTipo,
            'referencia_id'   => $referenciaId,
            'user_id'         => $userId,
        ]);
    }

    public function cerrar(CajaSesion $sesion, float $montoDeclarado, ?string $observaciones = null): CajaSesion
    {
        if ($sesion->estado !== 'abierta') {
            throw new RuntimeException('La caja ya está cerrada.');
        }

        $sistema = $sesion->saldoEsperado();

        $sesion->update([
            'estado'                => 'cerrada',
            'monto_final_declarado' => round($montoDeclarado, 2),
            'monto_final_sistema'   => $sistema,
            'diferencia'            => round($montoDeclarado - $sistema, 2),
            'observaciones'         => $observaciones ?: $sesion->observaciones,
            'cerrada_en'            => now(),
        ]);

        return $sesion;
    }
}
