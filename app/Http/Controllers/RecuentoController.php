<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Deposito;
use App\Models\Producto;
use App\Models\Recuento;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecuentoController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    public function index(): View
    {
        $recuentos = Recuento::with('deposito', 'user')->withCount('detalles')
            ->orderByDesc('id')->paginate(20);

        return view('recuentos.index', compact('recuentos'));
    }

    public function create(): View
    {
        $depositos = Deposito::activos()->orderByDesc('es_principal')->orderBy('nombre')->get();
        $categorias = Categoria::activas()->orderBy('nombre')->get();

        return view('recuentos.create', compact('depositos', 'categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'deposito_id' => ['required', 'exists:depositos,id'],
            'categoria_id' => ['nullable', 'exists:mongodb.categorias,_id'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $productos = Producto::where('estado', 'activo')
            ->when($data['categoria_id'] ?? null, fn ($q, $c) => $q->where('categoria_id', $c))
            ->orderBy('nombre')->get();

        abort_if($productos->isEmpty(), 422, 'No hay productos para contar.');

        $recuento = $this->inventario->crearRecuento((int) $data['deposito_id'], $productos, $data['observaciones'] ?? null);

        return redirect()->route('recuentos.show', $recuento)->with('success', 'Recuento creado. Cargá el conteo físico.');
    }

    public function show(Recuento $recuento): View
    {
        $recuento->load(['detalles.producto', 'deposito']);

        return view('recuentos.show', compact('recuento'));
    }

    public function guardar(Request $request, Recuento $recuento): RedirectResponse
    {
        $data = $request->validate([
            'contado' => ['array'],
            'contado.*' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $this->inventario->guardarConteo($recuento, $data['contado'] ?? []);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['contado' => $e->getMessage()]);
        }

        return back()->with('success', 'Conteo guardado.');
    }

    public function aplicar(Recuento $recuento): RedirectResponse
    {
        try {
            $this->inventario->aplicar($recuento);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['recuento' => $e->getMessage()]);
        }

        return redirect()->route('recuentos.show', $recuento)->with('success', 'Recuento aplicado. El stock quedó igual al conteo.');
    }
}
