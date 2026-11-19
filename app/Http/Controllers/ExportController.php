<?php

namespace App\Http\Controllers;

use App\Exports\ProductosExport;
use App\Exports\VentasExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /** Solo estos formatos: la extensión que manda el cliente no decide qué escritor se usa. */
    private function formato(Request $request): string
    {
        return $request->validate(['formato' => ['nullable', 'in:xlsx,csv']])['formato'] ?? 'xlsx';
    }

    public function productos(Request $request)
    {
        $formato = $this->formato($request);

        return Excel::download(new ProductosExport($request->user()->puedeVerCostos()), 'productos.'.$formato);
    }

    public function ventas(Request $request)
    {
        $formato = $this->formato($request);

        return Excel::download(
            new VentasExport(
                $request->user(),
                $request->get('fecha_desde'),
                $request->get('fecha_hasta'),
                $request->get('estado')
            ),
            'ventas.'.$formato
        );
    }
}
