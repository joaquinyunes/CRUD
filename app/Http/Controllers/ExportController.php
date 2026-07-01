<?php

namespace App\Http\Controllers;

use App\Exports\ProductosExport;
use App\Exports\VentasExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function productos(Request $request)
    {
        $formato = $request->get('formato', 'xlsx');

        return Excel::download(new ProductosExport, 'productos.' . $formato);
    }

    public function ventas(Request $request)
    {
        $formato = $request->get('formato', 'xlsx');

        return Excel::download(
            new VentasExport(
                $request->get('fecha_desde'),
                $request->get('fecha_hasta'),
                $request->get('estado')
            ),
            'ventas.' . $formato
        );
    }
}
