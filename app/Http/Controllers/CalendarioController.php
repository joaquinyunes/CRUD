<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventoRequest;
use App\Http\Requests\MoverEventoRequest;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
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

    public function store(EventoRequest $request): RedirectResponse
    {
        $validated = $request->datosParaGuardar();

        $validated['user_id'] = auth()->id();

        Evento::create($validated);

        return redirect()->route('calendario.index')
            ->with('success', 'Evento creado correctamente.');
    }

    public function update(EventoRequest $request, Evento $evento): RedirectResponse
    {
        $validated = $request->datosParaGuardar();

        $evento->update($validated);

        return redirect()->route('calendario.index')
            ->with('success', 'Evento actualizado correctamente.');
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        $evento->delete();

        return back()->with('success', 'Evento eliminado correctamente.');
    }

    /** Lo llama el calendario por AJAX al arrastrar un evento: responde JSON. */
    public function mover(MoverEventoRequest $request, Evento $evento): JsonResponse
    {
        $evento->update([
            'inicio' => $request->start,
            'fin'    => $request->end,
        ]);

        return response()->json(['success' => true]);
    }
}
