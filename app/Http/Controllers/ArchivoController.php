<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArchivoRequest;
use App\Models\Archivo;
use App\Models\User;
use App\Support\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $archivos = Orden::aplicar($query, [
            'nombre'   => 'nombre',
            'tipo'     => 'tipo',
            'tamano'   => 'tamano',
            'relacion' => 'relacionado_tipo',
            'usuario'  => User::select('name')->whereColumn('users.id', 'archivos.user_id'),
            'fecha'    => 'created_at',
        ], 'fecha', 'desc')->paginate(20)->withQueryString();

        return view('archivos.index', compact('archivos'));
    }

    public function store(ArchivoRequest $request): RedirectResponse
    {
        $file = $request->file('archivo');
        $nombreOriginal = $file->getClientOriginalName();
        $nombreGuardado = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        // Disco privado: se sirve solo a través de las rutas con permiso, nunca por URL directa.
        $ruta = $file->storeAs('archivos', $nombreGuardado, Archivo::DISCO);

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
        $disco = Storage::disk($archivo->disco());

        if ($disco->exists($archivo->ruta)) {
            $disco->delete($archivo->ruta);
        }

        $archivo->delete();

        return back()->with('success', 'Archivo eliminado correctamente.');
    }

    public function download(Archivo $archivo): StreamedResponse
    {
        abort_unless(Storage::disk($archivo->disco())->exists($archivo->ruta), 404);

        return Storage::disk($archivo->disco())->download($archivo->ruta, $archivo->nombre);
    }

    /** Muestra el archivo en el navegador (miniaturas y vista previa de PDF). */
    public function show(Archivo $archivo): StreamedResponse
    {
        abort_unless(Storage::disk($archivo->disco())->exists($archivo->ruta), 404);

        return Storage::disk($archivo->disco())->response($archivo->ruta, $archivo->nombre, [
            'Content-Type'           => $archivo->tipo,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
