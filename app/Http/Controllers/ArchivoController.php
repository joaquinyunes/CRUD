<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArchivoController extends Controller
{
    public function index(Request $request): View
    {
        $query = Archivo::with('user');

        if ($request->filled('relacionado_tipo') && $request->filled('relacionado_id')) {
            $query->paraModelo($request->relacionado_tipo, $request->relacionado_id);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('nombre', 'like', "%{$buscar}%");
        }

        $archivos = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('archivos.index', compact('archivos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo'           => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'relacionado_tipo'  => ['nullable', 'string', 'max:100'],
            'relacionado_id'    => ['nullable', 'integer'],
        ]);

        $file = $request->file('archivo');
        $nombreOriginal = $file->getClientOriginalName();
        $nombreGuardado = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        $ruta = $file->storeAs('archivos', $nombreGuardado, 'public');

        Archivo::create([
            'nombre'           => $nombreOriginal,
            'ruta'             => $ruta,
            'tipo'             => $file->getMimeType(),
            'tamano'           => $file->getSize(),
            'relacionado_tipo' => $request->relacionado_tipo,
            'relacionado_id'   => $request->relacionado_id,
            'user_id'          => auth()->id(),
        ]);

        return back()->with('success', 'Archivo subido correctamente.');
    }

    public function destroy(Archivo $archivo): RedirectResponse
    {
        if (Storage::disk('public')->exists($archivo->ruta)) {
            Storage::disk('public')->delete($archivo->ruta);
        }

        $archivo->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    public function download(Archivo $archivo)
    {
        $rutaCompleta = Storage::disk('public')->path($archivo->ruta);

        return response()->download($rutaCompleta, $archivo->nombre);
    }
}
