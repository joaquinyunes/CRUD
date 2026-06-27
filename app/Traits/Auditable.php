<?php

namespace App\Traits;

use App\Models\Auditoria;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            if (! auth()->check()) {
                return;
            }

            Auditoria::create([
                'user_id'          => auth()->id(),
                'ip'               => request()->ip(),
                'accion'           => 'created',
                'modelo_afectado'  => get_class($model),
                'modelo_id'        => $model->getKey(),
                'valor_anterior'   => null,
                'valor_nuevo'      => $model->getAttributes(),
                'created_at'       => now(),
            ]);
        });

        static::updated(function ($model) {
            if (! auth()->check()) {
                return;
            }

            $anterior = $model->getOriginal();
            $nuevo    = $model->getChanges();

            if (empty($nuevo)) {
                return;
            }

            unset($anterior['updated_at'], $nuevo['updated_at']);

            Auditoria::create([
                'user_id'          => auth()->id(),
                'ip'               => request()->ip(),
                'accion'           => 'updated',
                'modelo_afectado'  => get_class($model),
                'modelo_id'        => $model->getKey(),
                'valor_anterior'   => array_intersect_key($anterior, $nuevo),
                'valor_nuevo'      => $nuevo,
                'created_at'       => now(),
            ]);
        });

        static::deleted(function ($model) {
            if (! auth()->check()) {
                return;
            }

            Auditoria::create([
                'user_id'          => auth()->id(),
                'ip'               => request()->ip(),
                'accion'           => 'deleted',
                'modelo_afectado'  => get_class($model),
                'modelo_id'        => $model->getKey(),
                'valor_anterior'   => $model->getAttributes(),
                'valor_nuevo'      => null,
                'created_at'       => now(),
            ]);
        });

        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                if (! auth()->check()) {
                    return;
                }

                Auditoria::create([
                    'user_id'          => auth()->id(),
                    'ip'               => request()->ip(),
                    'accion'           => 'restored',
                    'modelo_afectado'  => get_class($model),
                    'modelo_id'        => $model->getKey(),
                    'valor_anterior'   => null,
                    'valor_nuevo'      => $model->getAttributes(),
                    'created_at'       => now(),
                ]);
            });
        }
    }
}
