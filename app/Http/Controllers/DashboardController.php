<?php

namespace App\Http\Controllers;

use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $ventasHoy      = 0;
        $ingresoHoy     = 0;
        $clientesNuevos = 0;

        $totalProductos = 0;
        $totalUsuarios  = User::count();
        $stockCritico   = 0;

        return view('dashboard', compact(
            'ventasHoy',
            'ingresoHoy',
            'clientesNuevos',
            'totalProductos',
            'totalUsuarios',
            'stockCritico'
        ));
    }
}
