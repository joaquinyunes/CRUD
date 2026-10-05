<?php

namespace App\Http\Controllers;

use App\Http\Requests\MetodoPagoRequest;
use App\Models\MetodoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MetodoPagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MetodoPago::query();

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', '%'.$request->input('buscar').'%')
                    ->orWhere('codigo', 'like', '%'.$request->input('buscar').'%');
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo') === 'activo');
        }

        $metodos = $query->orderBy('orden')->orderBy('nombre')->get();

        return response()->json($metodos);
    }

    public function store(MetodoPagoRequest $request): JsonResponse
    {
        $validated = $request->datosParaGuardar();

        $metodo = MetodoPago::create($validated);

        return response()->json([
            'message' => 'Método de pago creado correctamente.',
            'data'    => $metodo,
        ], Response::HTTP_CREATED);
    }

    public function update(MetodoPagoRequest $request, MetodoPago $metodo_pago): JsonResponse
    {
        $validated = $request->datosParaGuardar();

        $metodo_pago->update($validated);

        return response()->json([
            'message' => 'Método de pago actualizado correctamente.',
            'data'    => $metodo_pago,
        ]);
    }

    /**
     * Un método de pago usado en comprobantes se desactiva, no se borra: los
     * pagos ya cobrados lo referencian y tienen que seguir diciendo cómo se
     * cobraron.
     */
    public function destroy(MetodoPago $metodo_pago): JsonResponse
    {
        $usos = DB::table('ventas_pago')->where('metodo_pago_id', $metodo_pago->id)->count()
            + DB::table('compras_pago')->where('metodo_pago_id', $metodo_pago->id)->count();

        if ($usos > 0) {
            $metodo_pago->update(['activo' => false]);

            return response()->json([
                'message' => "Método de pago desactivado: se usó en {$usos} ".
                    ($usos === 1 ? 'pago' : 'pagos').' y no se puede borrar sin perder esa información. '.
                    'Ya no aparece al cargar comprobantes.',
            ]);
        }

        $metodo_pago->delete();

        return response()->json([
            'message' => 'Método de pago eliminado correctamente.',
        ]);
    }
}
