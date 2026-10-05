<?php

namespace App\Traits;

use App\Models\Auditoria;

/**
 * Registra en la tabla de auditoría cada alta, cambio y baja del modelo.
 *
 * Un modelo puede declarar $noAuditar para dejar afuera columnas que no tienen
 * que quedar escritas en ningún lado (un hash de contraseña, un token).
 */
trait Auditable
{
    /**
     * Columnas que nunca se copian a la auditoría.
     *
     * @var list<string>
     */
    protected array $noAuditarPorDefecto = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
    ];

    public static function bootAuditable(): void
    {
        static::created(fn (self $model) => $model->registrarAuditoria('created', null, $model->atributosAuditables()));

        static::updated(function (self $model) {
            $nuevo = $model->filtrarAuditoria($model->getChanges());
            unset($nuevo['updated_at']);

            if ($nuevo === []) {
                return;
            }

            $anterior = array_intersect_key($model->filtrarAuditoria($model->getOriginal()), $nuevo);

            $model->registrarAuditoria('updated', $anterior, $nuevo);
        });

        static::deleted(fn (self $model) => $model->registrarAuditoria('deleted', $model->atributosAuditables(), null));
    }

    /**
     * Guarda la entrada.
     *
     * Se registra siempre, también cuando no hay nadie autenticado: un cambio
     * hecho desde una tarea programada, un comando de consola o un job de cola
     * es exactamente el que más interesa poder rastrear después. En ese caso el
     * usuario queda nulo y el origen lo dice.
     *
     * @param  array<string,mixed>|null  $anterior
     * @param  array<string,mixed>|null  $nuevo
     */
    protected function registrarAuditoria(string $accion, ?array $anterior, ?array $nuevo): void
    {
        Auditoria::create([
            'user_id'         => auth()->id(),
            'ip'              => app()->runningInConsole() ? 'consola' : (request()->ip() ?? 'desconocida'),
            'accion'          => $accion,
            'modelo_afectado' => static::class,
            'modelo_id'       => $this->getKey(),
            'valor_anterior'  => $anterior,
            'valor_nuevo'     => $nuevo,
            'created_at'      => now(),
        ]);
    }

    /** @return array<string,mixed> */
    protected function atributosAuditables(): array
    {
        return $this->filtrarAuditoria($this->getAttributes());
    }

    /**
     * @param  array<string,mixed>  $atributos
     * @return array<string,mixed>
     */
    protected function filtrarAuditoria(array $atributos): array
    {
        $propias = property_exists($this, 'noAuditar') ? $this->noAuditar : [];
        $excluir = array_merge($this->noAuditarPorDefecto, $propias);

        return array_diff_key($atributos, array_flip($excluir));
    }
}
