<?php

namespace App\Support\Mongo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use MongoDB\Laravel\Relations\BelongsToMany;

/**
 * Muchos a muchos sin tabla intermedia (cada lado guarda la lista de ids del otro).
 *
 * El del paquete solo acepta arrays en sync() y attach(); el código de Laravel
 * suele pasar un id suelto o una Collection (`->pluck('id')`). Acá se normaliza.
 */
class RelacionMuchosAMuchos extends BelongsToMany
{
    public function sync($ids, $detaching = true)
    {
        return parent::sync($this->comoLista($ids), $detaching);
    }

    public function attach($id, array $attributes = [], $touch = true)
    {
        return parent::attach($this->comoLista($id), $attributes, $touch);
    }

    /** Sin ids, detach() tiene que soltar todo lo que el padre tiene (el del paquete no hace nada). */
    public function detach($ids = [], $touch = true)
    {
        if ($ids === [] || $ids === null) {
            $ids = (array) ($this->parent->{$this->relatedPivotKey} ?? []);
        }

        return parent::detach($ids === [] ? [] : $this->comoLista($ids), $touch);
    }

    private function comoLista(mixed $ids): mixed
    {
        if ($ids instanceof Arrayable && ! $ids instanceof Collection) {
            $ids = $ids->toArray();
        }

        if ($ids instanceof Model || $ids instanceof Collection) {
            return $ids;
        }

        return array_map(Consulta::entero(...), array_values((array) $ids));
    }
}
