<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Producto::with('categoria', 'unidadMedida')->activos();

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

        $productos = $query->orderBy('nombre')->paginate(20)->withQueryString();
        $categorias = Categoria::where('estado', true)->orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    /**
     * Búsqueda rápida para lectores de código de barras y autocompletado.
     * Coincidencia exacta por código/código de barra primero, luego por nombre/marca.
     */
    public function buscar(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $productos = Producto::where('estado', 'activo')
            ->where(function ($sql) use ($q) {
                $sql->where('codigo', $q)
                    ->orWhere('codigo_barra', $q)
                    ->orWhere('nombre', 'like', "%{$q}%")
                    ->orWhere('marca', 'like', "%{$q}%");
            })
            ->orderByRaw('CASE WHEN codigo = ? OR codigo_barra = ? THEN 0 ELSE 1 END', [$q, $q])
            ->orderBy('nombre')
            ->limit(10)
            ->get(['id', 'codigo', 'codigo_barra', 'nombre', 'precio_venta', 'precio_compra', 'stock']);

        return response()->json($productos);
    }

    public function create(): View
    {
        $categorias = Categoria::where('estado', true)->orderBy('nombre')->get();
        $unidadesMedida = UnidadMedida::where('estado', true)->orderBy('nombre')->get();

        return view('productos.form', compact('categorias', 'unidadesMedida'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validar($request);

        $validated['codigo'] = $validated['codigo'] ?? $this->generarCodigo();
        $validated['categoria_id'] = $validated['categoria_id'] ?? Categoria::where('estado', true)->orderBy('id')->value('id');
        $validated['precio_compra'] = $validated['precio_compra'] ?? 0;
        $validated['precio_venta'] = $validated['precio_venta'] ?? 0;
        $validated['stock_minimo'] = $validated['stock_minimo'] ?? 0;
        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['estado'] = $validated['estado'] ?? 'activo';
        $validated['descripcion'] = $validated['descripcion'] ?? '';

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
        $unidadesMedida = UnidadMedida::where('estado', true)->orderBy('nombre')->get();

        return view('productos.form', compact('producto', 'categorias', 'unidadesMedida'));
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
        $nuevo->codigo = $this->generarCodigoUnico($producto->codigo);
        $nuevo->nombre = $producto->nombre.' (copia)';
        $nuevo->stock = 0;
        $nuevo->imagen = null;
        $nuevo->estado = 'inactivo';
        $nuevo->save();

        return redirect()->route('productos.edit', $nuevo)
            ->with('success', 'Producto duplicado. Revisá los datos antes de activarlo.');
    }

    private function validar(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'codigo' => ['nullable', 'string', 'max:100',
                Rule::unique('productos', 'codigo')
                    ->ignore($ignorarId)
                    ->where(fn ($q) => $q->where('estado', '!=', 'eliminado'))],
            'codigo_barra' => ['nullable', 'string', 'max:100',
                Rule::unique('productos', 'codigo_barra')
                    ->ignore($ignorarId)
                    ->where(fn ($q) => $q->where('estado', '!=', 'eliminado'))],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:mongodb.categorias,_id'],
            'marca' => ['nullable', 'string', 'max:100'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'punto_pedido' => ['nullable', 'integer', 'min:0'],
            'unidad_medida_id' => ['nullable', 'exists:mongodb.unidades_medida,_id'],
            'proveedor_id' => ['nullable', 'exists:mongodb.proveedores,_id'],
            'es_pesable' => ['nullable', 'boolean'],
            'controla_vencimiento' => ['nullable', 'boolean'],
            'estado' => ['nullable', 'in:activo,inactivo'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]) + [
            'es_pesable' => $request->boolean('es_pesable'),
            'controla_vencimiento' => $request->boolean('controla_vencimiento'),
        ];
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
        $base = $codigoBase.'-COPIA';
        $codigo = $base;
        $i = 2;

        while (Producto::where('codigo', $codigo)->exists()) {
            $codigo = $base.'-'.$i;
            $i++;
        }

        return $codigo;
    }

    private function generarCodigo(): string
    {
        $ultimo = Producto::orderBy('id', 'desc')->value('id') ?? 0;

        return 'PROD-'.str_pad($ultimo + 1, 4, '0', STR_PAD_LEFT);
    }
}
