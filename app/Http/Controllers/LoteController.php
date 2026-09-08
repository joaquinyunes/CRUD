<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\Producto;
use App\Models\ProductoLote;
use App\Services\LoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoteController extends Controller
{
    public function __construct(private LoteService $lotes) {}

    public function index(Request $request): View
    {
        $porVencer = $this->lotes->porVencer();

        $lotes = ProductoLote::with('producto', 'deposito')
            ->conStock()
            ->when($request->filled('producto'), fn ($q) => $q->whereHas(
                'producto',
                fn ($p) => $p->where('nombre', 'like', '%'.$request->string('producto').'%')
            ))
            ->fefo()
            ->paginate(30)->withQueryString();

        $productos = Producto::where('estado', 'activo')->where('controla_vencimiento', true)->orderBy('nombre')->get(['id', 'nombre']);
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get(['id', 'nombre']);

        return view('lotes.index', compact('porVencer', 'lotes', 'productos', 'depositos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'deposito_id' => ['required', 'exists:depositos,id'],
            'lote' => ['nullable', 'string', 'max:60'],
            'vencimiento' => ['nullable', 'date'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
        ]);

        $this->lotes->ingresar(
            (int) $data['producto_id'],
            (int) $data['deposito_id'],
            (float) $data['cantidad'],
            $data['lote'] ?? null,
            $data['vencimiento'] ?? null,
        );

        return back()->with('success', 'Lote registrado.');
    }
}
