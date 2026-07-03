<?php

namespace App\Http\Controllers;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TareaController extends Controller
{
    public function index(Request $request): View
    {
        $vista = $request->get('vista', 'lista');

        $query = Tarea::with('asignada', 'creadaPor');

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where('titulo', 'like', "%{$buscar}%");
        }

        if ($request->filled('estado')) {
            $query->paraEstado($request->estado);
        }

        if ($request->filled('prioridad')) {
            $query->paraPrioridad($request->prioridad);
        }

        if ($request->filled('asignada_a')) {
            $query->paraUsuario($request->asignada_a);
        }

        $tareas = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $usuarios = User::orderBy('name')->get();

        if ($vista === 'kanban') {
            $pendientes = Tarea::with('asignada', 'creadaPor')
                               ->where('estado', 'pendiente')
                               ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
                               ->get();

            $enProgreso = Tarea::with('asignada', 'creadaPor')
                               ->where('estado', 'en_progreso')
                               ->orderByRaw("FIELD(prioridad, 'alta', 'media', 'baja')")
                               ->get();

            $completadas = Tarea::with('asignada', 'creadaPor')
                                ->where('estado', 'completada')
                                ->latest()
                                ->limit(20)
                                ->get();

            return view('tareas.kanban', compact('pendientes', 'enProgreso', 'completadas', 'usuarios'));
        }

        return view('tareas.index', compact('tareas', 'usuarios'));
    }

    public function create(): View
    {
        $usuarios = User::orderBy('name')->get();

        return view('tareas.form', compact('usuarios'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo'       => ['required', 'string', 'max:255'],
            'descripcion'  => ['nullable', 'string'],
            'prioridad'    => ['required', 'in:baja,media,alta'],
            'estado'       => ['required', 'in:pendiente,en_progreso,completada'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:today'],
            'asignada_a'   => ['nullable', 'exists:users,id'],
        ]);

        $validated['user_id'] = auth()->id();

        Tarea::create($validated);

        return redirect()->route('tareas.index')
                         ->with('success', 'Tarea creada correctamente.');
    }

    public function edit(Tarea $tarea): View
    {
        $usuarios = User::orderBy('name')->get();

        return view('tareas.form', compact('tarea', 'usuarios'));
    }

    public function update(Request $request, Tarea $tarea): RedirectResponse
    {
        $validated = $request->validate([
            'titulo'       => ['required', 'string', 'max:255'],
            'descripcion'  => ['nullable', 'string'],
            'prioridad'    => ['required', 'in:baja,media,alta'],
            'estado'       => ['required', 'in:pendiente,en_progreso,completada'],
            'fecha_limite' => ['nullable', 'date'],
            'asignada_a'   => ['nullable', 'exists:users,id'],
        ]);

        $tarea->update($validated);

        return redirect()->route('tareas.index')
                         ->with('success', 'Tarea actualizada correctamente.');
    }

    public function destroy(Tarea $tarea): RedirectResponse
    {
        $tarea->delete();

        return redirect()->route('tareas.index')
                         ->with('success', 'Tarea eliminada correctamente.');
    }

    public function cambiarEstado(Tarea $tarea, string $estado): RedirectResponse
    {
        $tarea->update(['estado' => $estado]);

        return back()->with('success', 'Estado de la tarea actualizado.');
    }
}
