@extends('layouts.app')

@section('page_title', 'Reporte de Ganancias')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Reporte de Ganancias</h2>
    <a href="{{ route('reportes.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.05">
    <form method="GET" action="{{ route('reportes.ganancias') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div>
            <label class="r-label">Fecha desde</label>
            <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="r-input" style="width:auto;">
        </div>
        <div>
            <label class="r-label">Fecha hasta</label>
            <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="r-input" style="width:auto;">
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->hasAny(['fecha_desde','fecha_hasta']))
            <a href="{{ route('reportes.ganancias') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-4);" class="r-mb-8">
    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0">
        <div class="r-kpi-value" style="font-size:1.5rem;">${{ number_format($totales['facturado'], 0, ',', '.') }}</div>
        <div class="r-kpi-label">Total Facturado</div>
    </div>
    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.05">
        <div class="r-kpi-value" style="font-size:1.5rem; color:#dc2626;">${{ number_format($totales['costo'], 0, ',', '.') }}</div>
        <div class="r-kpi-label">Costo Total</div>
    </div>
    <div class="r-kpi r-kpi-moss" data-reveal="fade-up" data-reveal-delay="0.1">
        <div class="r-kpi-value" style="font-size:1.5rem;">${{ number_format($totales['ganancia'], 0, ',', '.') }}</div>
        <div class="r-kpi-label">Ganancia Neta</div>
    </div>
    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.15">
        <div class="r-kpi-value r-kpi-marigold" style="font-size:1.5rem;">{{ number_format($totales['margen'], 1, ',', '.') }}%</div>
        <div class="r-kpi-label">Margen Promedio</div>
    </div>
    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.2">
        <div class="r-kpi-value" style="font-size:1.5rem;">{{ $totales['unidades'] }}</div>
        <div class="r-kpi-label">Unidades Vendidas</div>
    </div>
</div>

@if($porCategoria->count())
<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.25">
    <h3 class="r-body" style="font-weight:600; margin-bottom:var(--space-4);">Ganancia por Categoría</h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th style="text-align:right;">Facturado</th>
                    <th style="text-align:right;">Costo</th>
                    <th style="text-align:right;">Ganancia</th>
                    <th style="text-align:right;">Unidades</th>
                </tr>
            </thead>
            <tbody>
                @foreach($porCategoria as $cat => $datos)
                    <tr>
                        <td style="font-weight:500;">{{ $cat ?? 'Sin categoría' }}</td>
                        <td style="text-align:right;">${{ number_format($datos['facturado'], 2, ',', '.') }}</td>
                        <td style="text-align:right; color:#dc2626;">${{ number_format($datos['costo'], 2, ',', '.') }}</td>
                        <td style="text-align:right; color:#059669; font-weight:600;">${{ number_format($datos['ganancia'], 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ $datos['unidades'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.3">
    <h3 class="r-body" style="font-weight:600; margin-bottom:var(--space-4);">Ranking de Productos por Ganancia</h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th style="width:3rem; text-align:center;">#</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th style="text-align:right;">P. Compra</th>
                    <th style="text-align:right;">P. Venta</th>
                    <th style="text-align:right;">Unidades</th>
                    <th style="text-align:right;">Facturado</th>
                    <th style="text-align:right;">Ganancia</th>
                    <th style="text-align:right;">Margen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $index => $item)
                    <tr>
                        <td style="text-align:center; color:var(--color-ink-soft);">{{ $index + 1 }}</td>
                        <td><code style="font-size:0.75rem;">{{ $item->codigo }}</code></td>
                        <td style="font-weight:500;">{{ $item->nombre }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $item->categoria ?? '—' }}</td>
                        <td style="text-align:right;">${{ number_format($item->precio_compra, 2, ',', '.') }}</td>
                        <td style="text-align:right;">${{ number_format($item->precio_venta, 2, ',', '.') }}</td>
                        <td style="text-align:right;">{{ $item->unidades_vendidas }}</td>
                        <td style="text-align:right;">${{ number_format($item->total_facturado, 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:{{ $item->ganancia_total > 0 ? '#059669' : '#dc2626' }};">
                            ${{ number_format($item->ganancia_total, 2, ',', '.') }}
                        </td>
                        <td style="text-align:right;">
                            <span class="r-tag {{ $item->margen_porcentaje >= 30 ? 'r-tag-success' : ($item->margen_porcentaje >= 15 ? 'r-tag-marigold' : 'r-tag-danger') }}">
                                {{ number_format($item->margen_porcentaje, 1, ',', '.') }}%
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No hay ventas en este período.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($productos->count())
                <tfoot>
                    <tr>
                        <td colspan="5" class="r-label">Totales</td>
                        <td style="text-align:right; font-weight:600;">${{ number_format($totales['facturado'], 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600;">{{ $totales['unidades'] }}</td>
                        <td style="text-align:right; font-weight:600;">${{ number_format($totales['facturado'], 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:#059669;">${{ number_format($totales['ganancia'], 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($totales['margen'], 1, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

@endsection
