@extends('layouts.app')

@section('page_title', 'Reporte de negocio')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Reporte de negocio</h1>
    <a href="{{ route('reportes.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
</div>

<form method="GET" class="r-card-flat r-mb-6 r-flex r-gap-3" style="align-items:flex-end;flex-wrap:wrap;">
    <div><label class="r-label">Desde</label><input type="date" name="fecha_desde" value="{{ $desde }}" class="r-input"></div>
    <div><label class="r-label">Hasta</label><input type="date" name="fecha_hasta" value="{{ $hasta }}" class="r-input"></div>
    <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
</form>

<div class="r-flex r-gap-4 r-mb-6" style="flex-wrap:wrap;">
    <div class="r-card-flat" style="flex:1;min-width:160px;">
        <div class="r-caption">Ventas</div>
        <div class="r-display-m">{{ number_format($cantidad) }}</div>
    </div>
    <div class="r-card-flat" style="flex:1;min-width:160px;">
        <div class="r-caption">Facturado</div>
        <div class="r-display-m">${{ number_format($facturado, 2) }}</div>
    </div>
    <div class="r-card-flat" style="flex:1;min-width:160px;">
        <div class="r-caption">Ticket promedio</div>
        <div class="r-display-m">${{ number_format($ticketPromedio, 2) }}</div>
    </div>
    <div class="r-card-flat" style="flex:1;min-width:160px;">
        <div class="r-caption">Margen bruto</div>
        <div class="r-display-m">{{ number_format($margenBruto, 1) }}%</div>
    </div>
</div>

<div class="r-card-flat r-mb-6">
    <h2 class="r-label r-mb-4">Ventas por hora <span class="r-caption" style="text-transform:none;">— para dimensionar los turnos</span></h2>
    <div style="display:flex;align-items:flex-end;gap:3px;height:180px;">
        @foreach($porHora as $h => $d)
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;"
                 title="{{ sprintf('%02d:00', $h) }} — {{ $d['cantidad'] }} ventas — ${{ number_format($d['total'],2) }}">
                <div style="width:100%;background:var(--color-marigold,#e0a53d);border-radius:3px 3px 0 0;height:{{ $maxHora > 0 ? round($d['total'] / $maxHora * 100) : 0 }}%;min-height:{{ $d['total'] > 0 ? 2 : 0 }}px;"></div>
                <span style="font-size:0.6rem;color:var(--color-ink-soft);margin-top:4px;">{{ $h }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="r-card-flat">
    <h2 class="r-label r-mb-4">Sin rotación (30 días) — {{ $sinVenta->count() }} productos con stock</h2>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Código</th><th>Producto</th><th style="text-align:right;">Stock</th><th style="text-align:right;">Inmovilizado</th></tr></thead>
            <tbody>
            @forelse($sinVenta as $p)
                <tr>
                    <td class="r-caption"><code>{{ $p->codigo }}</code></td>
                    <td>{{ $p->nombre }}</td>
                    <td style="text-align:right;">{{ $p->stock }}</td>
                    <td style="text-align:right;">${{ number_format($p->stock * $p->precio_venta, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--color-ink-soft);">Todo el catálogo tuvo movimiento.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
