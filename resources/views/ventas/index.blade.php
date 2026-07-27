@extends('layouts.app')

@section('page_title', 'Ventas')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Ventas</h2>
    <div class="r-flex r-gap-3">
        @if (auth()->user()->role?->tienePermiso('ventas.exportar'))
            <a href="{{ route('exportar.ventas', array_merge(['formato' => 'xlsx'], request()->only(['fecha_desde', 'fecha_hasta', 'estado']))) }}" class="r-btn r-btn-ghost r-btn-sm">Exportar Excel</a>
        @endif
        @if (auth()->user()->role?->tienePermiso('ventas.crear'))
            <a href="{{ route('ventas.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva venta</a>
        @endif
    </div>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('ventas.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Número o cliente…" class="r-input">
        </div>
        <div>
            <label class="r-label">Fecha desde</label>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="r-input" style="width:auto;">
        </div>
        <div>
            <label class="r-label">Fecha hasta</label>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="r-input" style="width:auto;">
        </div>
        <div>
            <label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="">Todos</option>
                <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="completada" {{ request('estado') === 'completada' ? 'selected' : '' }}>Completada</option>
                <option value="cancelada" {{ request('estado') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
            </select>
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->hasAny(['buscar','fecha_desde','fecha_hasta','estado']))
            <a href="{{ route('ventas.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th style="text-align:right;">Total</th>
                    <th>Medio de Pago</th>
                    <th style="text-align:center;">Estado</th>
                    <th>Vendedor</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventas as $venta)
                    <tr>
                        <td><span class="r-mono" style="font-size:0.8125rem; color:var(--color-ink-soft);">{{ $venta->numero }}</span></td>
                        <td style="font-weight:500;">{{ $venta->cliente->nombre ?? '—' }} {{ $venta->cliente->apellido ?? '' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $venta->fecha->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:500;">${{ number_format($venta->total_final ?: $venta->total, 2, ',', '.') }}</td>
                        <td style="font-size:0.8125rem; color:var(--color-ink-soft);">
                            @if($venta->pagos && $venta->pagos->count())
                                @foreach($venta->pagos as $pago)
                                    <span class="r-tag r-tag-sm" style="font-size:0.6875rem;">{{ $pago->metodoPago->nombre ?? '—' }}: ${{ number_format($pago->monto, 2, ',', '.') }}</span>
                                    @if(!$loop->last) @endif
                                @endforeach
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($venta->estado === 'completada')
                                <span class="r-tag r-tag-success">Completada</span>
                            @elseif($venta->estado === 'pendiente')
                                <span class="r-tag r-tag-marigold">Pendiente</span>
                            @else
                                <span class="r-tag r-tag-danger">Cancelada</span>
                            @endif
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $venta->user->name ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if (auth()->user()->role?->tienePermiso('ventas.ver'))
                                    <a href="{{ route('ventas.show', $venta) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Ver</a>
                                @endif
                                <a href="{{ route('pdf.venta', $venta) }}" target="_blank" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#4f46e5;">Factura</a>
                                @if (auth()->user()->role?->tienePermiso('ventas.editar'))
                                    <a href="{{ route('ventas.edit', $venta) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if (auth()->user()->role?->tienePermiso('ventas.eliminar'))
                                    <form method="POST" action="{{ route('ventas.destroy', $venta) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;" onclick="return confirm('¿Eliminar esta venta?')">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron ventas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($ventas->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $ventas->firstItem() }}–{{ $ventas->lastItem() }} de {{ $ventas->total() }} ventas</span>
        {{ $ventas->links() }}
    </div>
@endif

@endsection
