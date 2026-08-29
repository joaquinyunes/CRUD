<?php

namespace App\Http\Controllers;

use App\Models\Deposito;
use App\Models\Producto;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DepositoController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(): View
    {
        $depositos = Deposito::orderByDesc('es_principal')->orderBy('nombre')->get();

        $totales = DB::table('stock_deposito')
            ->select('deposito_id', DB::raw('SUM(cantidad) as unidades'))
            ->groupBy('deposito_id')
            ->pluck('unidades', 'deposito_id');

        return view('depositos.index', compact('depositos', 'totales'));
    }

    public function create(): View
    {
        return view('depositos.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);
        $deposito = Deposito::create($data + ['activo' => true]);
        $this->sincronizarPrincipal($deposito);

        return redirect()->route('depositos.index')->with('success', 'Depósito creado.');
    }

    public function edit(Deposito $deposito): View
    {
        return view('depositos.form', compact('deposito'));
    }

    public function update(Request $request, Deposito $deposito): RedirectResponse
    {
        $deposito->update($this->validar($request));
        $this->sincronizarPrincipal($deposito);

        return redirect()->route('depositos.index')->with('success', 'Depósito actualizado.');
    }

    public function destroy(Deposito $deposito): RedirectResponse
    {
        if ($deposito->es_principal) {
            return back()->withErrors(['general' => 'No se puede desactivar el depósito principal.']);
        }
        $deposito->update(['activo' => false]);

        return redirect()->route('depositos.index')->with('success', 'Depósito desactivado.');
    }

    public function stock(Request $request, Deposito $deposito): View
    {
        $productos = Producto::where('estado', 'activo')
            ->when($request->filled('buscar'), fn ($q) => $q->where(fn ($s) => $s
                ->where('nombre', 'like', "%{$request->buscar}%")
                ->orWhere('codigo', $request->buscar)
                ->orWhere('codigo_barra', $request->buscar)))
            ->orderBy('nombre')
            ->paginate(30)->withQueryString();

        $cantidades = DB::table('stock_deposito')
            ->where('deposito_id', $deposito->id)
            ->pluck('cantidad', 'producto_id');

        return view('depositos.stock', compact('deposito', 'productos', 'cantidades'));
    }

    public function transferForm(): View
    {
        $depositos = Deposito::activos()->orderBy('nombre')->get();
        $productos = Producto::where('estado', 'activo')->orderBy('nombre')->get();

        return view('depositos.transferir', compact('depositos', 'productos'));
    }

    public function transferir(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'origen_id'   => ['required', 'exists:depositos,id'],
            'destino_id'  => ['required', 'exists:depositos,id', 'different:origen_id'],
            'cantidad'    => ['required', 'integer', 'min:1'],
        ]);

        try {
            $this->stock->transferir(
                Producto::findOrFail($data['producto_id']),
                $data['cantidad'],
                (int) $data['origen_id'],
                (int) $data['destino_id'],
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['cantidad' => $e->getMessage()])->withInput();
        }

        return redirect()->route('depositos.index')->with('success', 'Transferencia registrada.');
    }

    private function sincronizarPrincipal(Deposito $deposito): void
    {
        if ($deposito->es_principal) {
            Deposito::where('id', '!=', $deposito->id)->update(['es_principal' => false]);
        } elseif (! Deposito::where('es_principal', true)->exists()) {
            $deposito->update(['es_principal' => true]);
        }
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre'       => ['required', 'string', 'max:255'],
            'direccion'    => ['nullable', 'string', 'max:255'],
            'es_principal' => ['nullable', 'boolean'],
        ]);
    }
}
