@extends('layouts.app')

@section('page_title', 'Compras')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Compras</h2>
    @if (auth()->user()->role?->tienePermiso('compras.crear'))
        <a href="{{ route('compras.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva compra</a>
    @endif
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('compras.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Número o proveedor…" class="r-input">
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
            <a href="{{ route('compras.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:center;">Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $compra)
                    <tr>
                        <td><span class="r-mono" style="font-size:0.8125rem; color:var(--color-ink-soft);">{{ $compra->numero }}</span></td>
                        <td style="font-weight:500;">{{ $compra->proveedor->nombre ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $compra->fecha->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-weight:500;">${{ number_format($compra->total, 2, ',', '.') }}</td>
                        <td style="text-align:center;">
                            @if($compra->estado === 'completada')
                                <span class="r-tag r-tag-success">Completada</span>
                            @elseif($compra->estado === 'pendiente')
                                <span class="r-tag r-tag-marigold">Pendiente</span>
                            @else
                                <span class="r-tag r-tag-danger">Cancelada</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if (auth()->user()->role?->tienePermiso('compras.ver'))
                                    <a href="{{ route('compras.show', $compra) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Ver</a>
                                @endif
                                @if (auth()->user()->role?->tienePermiso('compras.editar'))
                                    <a href="{{ route('compras.edit', $compra) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if (auth()->user()->role?->tienePermiso('compras.eliminar'))
                                    <form method="POST" action="{{ route('compras.destroy', $compra) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;" onclick="return confirm('¿Eliminar esta compra?')">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron compras.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($compras->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $compras->firstItem() }}–{{ $compras->lastItem() }} de {{ $compras->total() }} compras</span>
        {{ $compras->links() }}
    </div>
@endif

@endsection
