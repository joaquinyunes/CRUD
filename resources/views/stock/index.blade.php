@extends('layouts.app')

@section('page_title', 'Movimientos de Stock')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Movimientos de Stock</h2>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('stock.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Producto</label>
            <select name="producto_id" class="r-select" style="width:100%;">
                <option value="">Todos</option>
                @foreach($productos as $prod)
                    <option value="{{ $prod->id }}" {{ request('producto_id') == $prod->id ? 'selected' : '' }}>{{ $prod->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="r-label">Tipo</label>
            <select name="tipo" class="r-select">
                <option value="">Todos</option>
                <option value="entrada" {{ request('tipo') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                <option value="salida" {{ request('tipo') === 'salida' ? 'selected' : '' }}>Salida</option>
                <option value="ajuste" {{ request('tipo') === 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                <option value="devolucion" {{ request('tipo') === 'devolucion' ? 'selected' : '' }}>Devolución</option>
            </select>
        </div>
        <div>
            <label class="r-label">Desde</label>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="r-input" style="width:auto;">
        </div>
        <div>
            <label class="r-label">Hasta</label>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="r-input" style="width:auto;">
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->hasAny(['producto_id','tipo','fecha_desde','fecha_hasta']))
            <a href="{{ route('stock.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th style="text-align:center;">Tipo</th>
                    <th style="text-align:center;">Cantidad</th>
                    <th>Referencia</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                    <tr>
                        <td style="color:var(--color-ink-soft);">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-weight:500;">{{ $mov->producto->nombre ?? '—' }}</td>
                        <td style="text-align:center;">
                            @if($mov->tipo === 'entrada')
                                <span class="r-tag r-tag-success">Entrada</span>
                            @elseif($mov->tipo === 'salida')
                                <span class="r-tag r-tag-danger">Salida</span>
                            @elseif($mov->tipo === 'ajuste')
                                <span class="r-tag r-tag-marigold">Ajuste</span>
                            @else
                                <span class="r-tag" style="background:rgba(79,70,229,0.1); color:#4f46e5;">Devolución</span>
                            @endif
                        </td>
                        <td style="text-align:center; font-weight:600; {{ $mov->tipo === 'salida' ? 'color:#dc2626;' : 'color:#16a34a;' }}">
                            {{ $mov->tipo === 'salida' ? '-' : '+' }}{{ $mov->cantidad }}
                        </td>
                        <td style="color:var(--color-ink-soft);">
                            @if($mov->referencia_tipo && $mov->referencia_id)
                                {{ ucfirst($mov->referencia_tipo) }} #{{ $mov->referencia_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $mov->user->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron movimientos de stock.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($movimientos->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $movimientos->firstItem() }}–{{ $movimientos->lastItem() }} de {{ $movimientos->total() }} movimientos</span>
        {{ $movimientos->links() }}
    </div>
@endif

@endsection
