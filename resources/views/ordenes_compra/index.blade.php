@extends('layouts.app')

@section('page_title', 'Órdenes de compra')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Órdenes de compra</h2>
    @if(auth()->user()->role?->tienePermiso('ordenes_compra.crear'))
        <a href="{{ route('ordenes-compra.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva orden</a>
    @endif
</div>

<div class="r-card-flat r-mb-6">
    <form method="GET" class="r-flex r-gap-3" style="align-items:flex-end;">
        <div><label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="">Todos</option>
                @foreach(['borrador','enviada','parcial','recibida','cancelada'] as $e)
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
            <thead><tr><th>Número</th><th>Proveedor</th><th>Fecha</th><th>Entrega</th><th>Estado</th><th style="text-align:right;">Total</th><th></th></tr></thead>
            <tbody>
            @forelse($ordenes as $o)
                <tr>
                    <td>{{ $o->numero }}</td>
                    <td>{{ $o->proveedor->nombre ?? '—' }}</td>
                    <td>{{ $o->fecha->format('d/m/Y') }}</td>
                    <td style="color:var(--color-ink-soft);">{{ $o->fecha_entrega_estimada?->format('d/m/Y') ?? '—' }}</td>
                    <td><span class="r-tag">{{ ucfirst($o->estado) }}</span></td>
                    <td style="text-align:right;">${{ number_format($o->total, 2, ',', '.') }}</td>
                    <td style="text-align:right;"><a href="{{ route('ordenes-compra.show', $o) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin órdenes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($ordenes->hasPages())<div class="r-mt-6">{{ $ordenes->links() }}</div>@endif
@endsection
