<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarioController extends Controller
{
    public function index(): View
    {
        $eventos = Evento::with('user')->get();

        return view('calendario.index', compact('eventos'));
    }

    public function eventosJson(Request $request)
    {
        $query = Evento::query();

        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('inicio', [$request->start, $request->end]);
        }

        $eventos = $query->get()->map(function ($evento) {
            return [
                'id'          => $evento->id,
                'title'       => $evento->titulo,
                'start'       => $evento->start,
                'end'         => $evento->end,
                'allDay'      => $evento->todo_el_dia,
                'color'       => $evento->color,
                'description' => $evento->descripcion,
            ];
        });

        return response()->json($eventos);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:7'],
            'inicio'      => ['required', 'date'],
            'fin'         => ['nullable', 'date', 'after_or_equal:inicio'],
            'todo_el_dia' => ['nullable', 'boolean'],
        ]);

        $validated['user_id'] = auth()->id();
        $validated['todo_el_dia'] = $request->boolean('todo_el_dia');

        Evento::create($validated);

        return redirect()->route('calendario.index')
                         ->with('success', 'Evento creado correctamente.');
    }

    public function update(Request $request, Evento $evento): RedirectResponse
    {
        $validated = $request->validate([
            'titulo'      => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'color'       => ['nullable', 'string', 'max:7'],
            'inicio'      => ['required', 'date'],
            'fin'         => ['nullable', 'date', 'after_or_equal:inicio'],
            'todo_el_dia' => ['nullable', 'boolean'],
        ]);

        $validated['todo_el_dia'] = $request->boolean('todo_el_dia');

        $evento->update($validated);

        return redirect()->route('calendario.index')
                         ->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        $evento->delete();

        return back()->with('success', 'Evento eliminado correctamente.');
    }

    public function mover(Request $request, Evento $evento): RedirectResponse
    {
        $evento->update([
            'inicio' => $request->start,
            'fin'    => $request->end,
        ]);

        return response()->json(['success' => true]);
    }
}
