@extends('layouts.app')

@section('page_title', 'Reportes')

@section('content')

<h2 class="r-display-l r-mb-8" data-reveal="fade-up">Reportes</h2>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:var(--space-4);" class="r-mb-8">
    <a href="{{ route('reportes.ventas-periodo') }}" class="r-card" data-reveal="fade-up" data-reveal-delay="0">
        <span class="r-caption">Ventas por período</span>
        <div class="r-kpi-value r-mt-2" style="font-size:1.75rem;">${{ number_format($ventasHoy, 2, ',', '.') }}</div>
        <span class="r-kpi-label">Hoy</span>
    </a>
    <a href="{{ route('reportes.productos-vendidos') }}" class="r-card" data-reveal="fade-up" data-reveal-delay="0.05">
        <span class="r-caption">Productos más vendidos</span>
        <div class="r-kpi-value r-mt-2" style="font-size:1.75rem;">${{ number_format($ventasMes, 2, ',', '.') }}</div>
        <span class="r-kpi-label">Este mes · {{ $totalVentasMes }} ventas</span>
    </a>
    <a href="{{ route('reportes.mejores-clientes') }}" class="r-card" data-reveal="fade-up" data-reveal-delay="0.1">
        <span class="r-caption">Mejores clientes</span>
        <div class="r-kpi-value r-kpi-moss r-mt-2" style="font-size:1.75rem;">{{ $totalClientes ?? 0 }}</div>
        <span class="r-kpi-label">Clientes activos</span>
    </a>
    <a href="{{ route('reportes.stock-critico') }}" class="r-card" data-reveal="fade-up" data-reveal-delay="0.15">
        <span class="r-caption">Stock crítico</span>
        <div class="r-kpi-value r-mt-2" style="font-size:1.75rem; color:#dc2626;">{{ $productosAgotados }}</div>
        <span class="r-kpi-label">Productos agotados</span>
    </a>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:var(--space-6);">
    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
        <h3 class="r-display-m" style="font-size:1rem; margin-bottom:var(--space-4);">Accesos rápidos</h3>
        <div style="display:flex; flex-direction:column; gap:var(--space-1);">
            <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'diario']) }}" class="r-sidebar-link" style="color:var(--color-ink); padding:8px 12px;">→ Ventas diarias (últimos 30 días)</a>
            <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'semanal']) }}" class="r-sidebar-link" style="color:var(--color-ink); padding:8px 12px;">→ Ventas semanales (últimas 12 semanas)</a>
            <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'mensual']) }}" class="r-sidebar-link" style="color:var(--color-ink); padding:8px 12px;">→ Ventas mensuales (últimos 12 meses)</a>
            <a href="{{ route('reportes.productos-vendidos') }}" class="r-sidebar-link" style="color:var(--color-ink); padding:8px 12px;">→ Top 10 productos más vendidos</a>
            <a href="{{ route('reportes.mejores-clientes') }}" class="r-sidebar-link" style="color:var(--color-ink); padding:8px 12px;">→ Top 10 mejores clientes</a>
            <a href="{{ route('reportes.stock-critico') }}" class="r-sidebar-link" style="color:var(--color-ink); padding:8px 12px;">→ Stock crítico y agotado</a>
        </div>
    </div>
    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.25">
        <h3 class="r-display-m" style="font-size:1rem; margin-bottom:var(--space-4);">Resumen del mes</h3>
        <div style="display:flex; flex-direction:column; gap:var(--space-3);">
            <div class="r-flex r-justify-between"><span style="color:var(--color-ink-soft);">Ventas completadas</span><span style="font-weight:600;">{{ $totalVentasMes }}</span></div>
            <div class="r-flex r-justify-between"><span style="color:var(--color-ink-soft);">Facturación total</span><span style="font-weight:600;">${{ number_format($ventasMes, 2, ',', '.') }}</span></div>
            <div class="r-flex r-justify-between"><span style="color:var(--color-ink-soft);">Ticket promedio</span><span style="font-weight:600;">${{ $totalVentasMes > 0 ? number_format($ventasMes / $totalVentasMes, 2, ',', '.') : '0.00' }}</span></div>
            <div class="r-flex r-justify-between"><span style="color:var(--color-ink-soft);">Stock crítico</span><span class="r-tag r-tag-marigold">{{ $productosStockCritico }} productos</span></div>
            <div class="r-flex r-justify-between"><span style="color:var(--color-ink-soft);">Productos agotados</span><span class="r-tag r-tag-danger">{{ $productosAgotados }} productos</span></div>
        </div>
    </div>
</div>

@endsection
