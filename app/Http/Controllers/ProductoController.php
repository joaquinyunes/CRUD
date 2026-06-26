<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Producto::with('categoria')->activos();

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('codigo', 'like', "%{$buscar}%")
                  ->orWhere('marca', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $productos   = $query->orderBy('nombre')->paginate(20)->withQueryString();
        $categorias  = Categoria::where('estado', true)->orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function create(): View
    {
        $categorias = Categoria::where('estado', true)->orderBy('nombre')->get();

        return view('productos.form', compact('categorias'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);

        $validated['imagen'] = $this->manejarImagen($request);

        Producto::create($validated);

        return redirect()->route('productos.index')
                         ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto): View
    {
        if ($producto->estado === 'eliminado') {
            abort(404);
        }

        $categorias = Categoria::where('estado', true)->orderBy('nombre')->get();

        return view('productos.form', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        if ($producto->estado === 'eliminado') {
            abort(404);
        }

        $validated = $this->validar($request, $producto->id);

        if ($request->hasFile('imagen')) {
            $this->eliminarImagenAnterior($producto);
            $validated['imagen'] = $this->manejarImagen($request);
        } else {
            unset($validated['imagen']);
        }

        $producto->update($validated);

        return redirect()->route('productos.index')
                         ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->update(['estado' => 'eliminado']);

        return redirect()->route('productos.index')
                         ->with('success', 'Producto eliminado correctamente.');
    }

    public function duplicar(Producto $producto): RedirectResponse
    {
        if ($producto->estado === 'eliminado') {
            abort(404);
        }

        $nuevo = $producto->replicate();
        $nuevo->codigo  = $this->generarCodigoUnico($producto->codigo);
        $nuevo->nombre  = $producto->nombre . ' (copia)';
        $nuevo->stock   = 0;
        $nuevo->imagen  = null;
        $nuevo->estado  = 'inactivo';
        $nuevo->save();

        return redirect()->route('productos.edit', $nuevo)
                         ->with('success', 'Producto duplicado. Revisá los datos antes de activarlo.');
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo'        => ['required', 'string', 'max:100',
                                Rule::unique('productos', 'codigo')
                                    ->ignore($ignorarId)
                                    ->where(fn ($q) => $q->where('estado', '!=', 'eliminado'))],
            'nombre'        => ['required', 'string', 'max:255'],
            'descripcion'   => ['nullable', 'string'],
            'categoria_id'  => ['required', 'exists:categorias,id'],
            'marca'         => ['nullable', 'string', 'max:100'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'precio_venta'  => ['required', 'numeric', 'min:0'],
            'stock_minimo'  => ['required', 'integer', 'min:0'],
            'estado'        => ['required', 'in:activo,inactivo'],
            'imagen'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function manejarImagen(Request $request): ?string
    {
        if ($request->hasFile('imagen')) {
            return $request->file('imagen')->store('productos', 'public');
        }
        return null;
    }

    private function eliminarImagenAnterior(Producto $producto): void
    {
        if ($producto->imagen && Storage::disk('public')->exists($producto->imagen)) {
            Storage::disk('public')->delete($producto->imagen);
        }
    }

    private function generarCodigoUnico(string $codigoBase): string
    {
        $base   = $codigoBase . '-COPIA';
        $codigo = $base;
        $i      = 2;

        while (Producto::where('codigo', $codigo)->exists()) {
            $codigo = $base . '-' . $i;
            $i++;
        }

        return $codigo;
    }
}
