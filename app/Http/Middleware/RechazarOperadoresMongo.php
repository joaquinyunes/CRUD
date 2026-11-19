<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ninguna pantalla ni endpoint de este sistema manda claves que empiecen con «$»
 * (`?categoria_id[$ne]=0`, `{"email":{"$gt":""}}`): son operadores de MongoDB y solo
 * sirven para colar condiciones en una consulta. Se cortan en la puerta, en
 * cualquier parte de la query o del cuerpo, sin depender de que cada controlador
 * se acuerde de castear.
 */
class RechazarOperadoresMongo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tieneOperador($request->query->all()) || $this->tieneOperador($request->request->all()) || $this->tieneOperador($request->json()->all())) {
            abort(400, 'La petición trae operadores no permitidos.');
        }

        return $next($request);
    }

    /** @param  array<int|string,mixed>  $datos */
    private function tieneOperador(array $datos): bool
    {
        foreach ($datos as $clave => $valor) {
            if (is_string($clave) && str_starts_with($clave, '$')) {
                return true;
            }

            if (is_array($valor) && $this->tieneOperador($valor)) {
                return true;
            }
        }

        return false;
    }
}
