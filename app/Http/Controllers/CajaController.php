<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caja\AbrirCajaRequest;
use App\Http\Requests\Caja\CerrarCajaRequest;
use App\Http\Requests\Caja\MovimientoCajaRequest;
use App\Models\CajaSesion;
use App\Services\CajaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CajaController extends Controller
{
    public function __construct(private CajaService $caja) {}

    public function index(): View
    {
        $sesion = $this->caja->sesionAbierta(auth()->id());
        $sesion?->load(['movimientos.user']);

        $historial = CajaSesion::with('user')
            ->where('estado', 'cerrada')
            ->orderByDesc('cerrada_en')
            ->limit(15)->get();

        return view('caja.index', compact('sesion', 'historial'));
    }

    public function abrir(AbrirCajaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->caja->abrir(auth()->user(), $data['monto_inicial'], $data['observaciones'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['monto_inicial' => $e->getMessage()]);
        }

        return redirect()->route('caja.index')->with('success', 'Caja abierta.');
    }

    public function movimiento(MovimientoCajaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $sesion = $this->caja->sesionAbierta(auth()->id());
        if (! $sesion) {
            return back()->withErrors(['monto' => 'No tenés una caja abierta.']);
        }

        $this->caja->registrarMovimiento($data['tipo'], $data['concepto'], $data['monto'], 'manual', null);

        return back()->with('success', 'Movimiento registrado.');
    }

    public function cerrar(CerrarCajaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $sesion = $this->caja->sesionAbierta(auth()->id());
        if (! $sesion) {
            return back()->withErrors(['monto_final_declarado' => 'No tenés una caja abierta.']);
        }

        $this->caja->cerrar($sesion, $data['monto_final_declarado'], $data['observaciones'] ?? null);

        return redirect()->route('caja.index')->with('success', 'Caja cerrada. Revisá el arqueo.');
    }
}
