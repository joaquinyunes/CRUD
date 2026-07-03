<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    public function index(Request $request): View
    {
        $notificaciones = Notificacion::paraUsuario(auth()->id())
                                      ->orderBy('created_at', 'desc')
                                      ->paginate(20);

        $noLeidas = Notificacion::paraUsuario(auth()->id())
                                ->noLeidas()
                                ->count();

        return view('notificaciones.index', compact('notificaciones', 'noLeidas'));
    }

    public function marcarLeida(Notificacion $notificacion): RedirectResponse
    {
        if ($notificacion->user_id !== auth()->id()) {
            abort(403);
        }

        $notificacion->update(['leida' => true]);

        if ($notificacion->url) {
            return redirect($notificacion->url);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function marcarTodasLeidas(): RedirectResponse
    {
        Notificacion::paraUsuario(auth()->id())
                    ->noLeidas()
                    ->update(['leida' => true]);

        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }

    public function noLeidasCount(): int
    {
        return Notificacion::paraUsuario(auth()->id())
                           ->noLeidas()
                           ->count();
    }
}
