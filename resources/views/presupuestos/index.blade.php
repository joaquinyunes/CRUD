@extends('layouts.app')

@section('page_title', 'Presupuestos')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Presupuestos</h2>
    @if(auth()->user()->role?->tienePermiso('presupuestos.crear'))
        <a href="{{ route('presupuestos.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo presupuesto</a>
    @endif
</div>

<div class="r-card-flat r-mb-6">
    <form method="GET" class="r-flex r-gap-3" style="align-items:flex-end; flex-wrap:wrap;">
        <div><label class="r-label">Buscar Nº</label><input type="text" name="buscar" value="{{ request('buscar') }}" class="r-input"></div>
        <div><label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="">Todos</option>
                @foreach(['borrador','enviado','aceptado','rechazado','convertido','vencido'] as $e)
                    <option value="{{ $e }}" @selected(request('estado')===$e)>{{ ucfirst($e) }}</option>
                @endforeach
            </select>
        </div>
        <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
    </form>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><x-th campo="numero">Número</x-th><x-th campo="cliente">Cliente</x-th><x-th campo="fecha" inicial="desc">Fecha</x-th><x-th campo="validez" inicial="desc">Validez</x-th><x-th campo="estado">Estado</x-th><x-th campo="total" align="right" inicial="desc">Total</x-th><th></th></tr></thead>
            <tbody>
            @forelse($presupuestos as $p)
                <tr>
                    <td>{{ $p->numero }}</td>
                    <td>{{ $p->cliente->nombre ?? '' }} {{ $p->cliente->apellido ?? '' }}</td>
                    <td>{{ $p->fecha->format('d/m/Y') }}</td>
                    <td style="color:var(--color-ink-soft);">{{ $p->estaVencido() ? 'Vencido' : $p->fecha->copy()->addDays($p->validez_dias)->format('d/m/Y') }}</td>
                    <td><span class="r-tag">{{ ucfirst($p->estado) }}</span></td>
                    <td style="text-align:right;">${{ number_format($p->total, 2, ',', '.') }}</td>
                    <td style="text-align:right;"><a href="{{ route('presupuestos.show', $p) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin presupuestos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($presupuestos->hasPages())<div class="r-mt-6">{{ $presupuestos->links() }}</div>@endif
@endsection
