<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuficienteException;
use App\Models\Deposito;
use App\Models\Merma;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MermaController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    public function index(Request $request): View
    {
        $mermas = Merma::with('producto', 'deposito', 'user')
            ->when($request->filled('motivo'), fn ($q) => $q->where('motivo', $request->motivo))
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $totalMes = Merma::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('costo');

        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']);
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get(['id', 'nombre']);

        return view('mermas.index', compact('mermas', 'totalMes', 'productos', 'depositos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'deposito_id' => ['required', 'exists:depositos,id'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['required', 'in:'.implode(',', Merma::MOTIVOS)],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->inventario->registrarMerma(
                Producto::findOrFail($data['producto_id']),
                (int) $data['deposito_id'],
                (int) $data['cantidad'],
                $data['motivo'],
                $data['observaciones'] ?? null,
            );
        } catch (StockInsuficienteException|\RuntimeException $e) {
            return back()->withErrors(['cantidad' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Merma registrada. El stock fue ajustado.');
    }
}
