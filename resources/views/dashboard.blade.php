@extends('layouts.app')

@section('title', 'Panel — ' . config('app.name'))
@section('page_title', 'Panel')

@section('content')

{{-- Hero Section --}}
<div class="r-mb-12" data-reveal="fade-up">
    <h2 class="r-display-xl r-mb-4">
        Hola, {{ explode(' ', Auth::user()->name)[0] }}.
    </h2>
    <p class="r-body-l" style="max-width: 520px;">
        Este es tu resumen de actividad. Filtrá por período para ver los números que importan.
    </p>

    {{-- Rhythm divider --}}
    <div class="r-divider" style="justify-content: flex-start; padding: var(--space-6) 0;">
        <svg viewBox="0 0 120 24" width="120" height="24">
            <path class="r-divider-line" d="M0,12 Q10,4 20,12 T40,12 T60,12 T80,12 T100,12 T120,12" />
        </svg>
    </div>
</div>

{{-- Period Filter --}}
<div class="r-card-flat r-mb-8" data-reveal="fade-up" data-reveal-delay="0.1">
    <div class="r-flex r-items-center r-justify-between" style="flex-wrap: wrap; gap: var(--space-4);">
        <div>
            <span class="r-caption">Período</span>
            <p style="margin: var(--space-1) 0 0; font-family: var(--font-body); color: var(--color-ink-soft);">
                {{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}
            </p>
        </div>
        <form method="GET" class="r-flex r-items-center r-gap-3">
            <input type="date" name="fecha_desde" value="{{ $fechaDesde }}" class="r-input" style="width: auto;">
            <span style="color: var(--color-ink-soft);">—</span>
            <input type="date" name="fecha_hasta" value="{{ $fechaHasta }}" class="r-input" style="width: auto;">
            <button type="submit" class="r-btn r-btn-accent r-btn-sm">
                Filtrar
            </button>
        </form>
    </div>
</div>

{{-- KPIs Principales --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-4);" class="r-mb-8">

    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0">
        <div class="r-kpi-value counter-animate" data-target="{{ $ventasHoy }}">{{ $ventasHoy }}</div>
        <div class="r-kpi-label">Ventas Hoy</div>
    </div>

    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.05">
        <div class="r-kpi-value r-kpi-marigold counter-animate" data-target="{{ $ingresoHoy }}">${{ number_format($ingresoHoy, 0, ',', '.') }}</div>
        <div class="r-kpi-label">Ingresos Hoy</div>
    </div>

    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.1">
        <div class="r-kpi-value r-kpi-moss counter-animate" data-target="{{ $ventasMes }}">${{ number_format($ventasMes, 0, ',', '.') }}</div>
        <div class="r-kpi-label">Ventas del Mes</div>
        @if($variacionVentas != 0)
            <span style="font-size:0.75rem; color:{{ $variacionVentas > 0 ? '#059669' : '#dc2626' }};">
                {{ $variacionVentas > 0 ? '↑' : '↓' }} {{ number_format(abs($variacionVentas), 1) }}% vs mes anterior
            </span>
        @endif
    </div>

    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.15">
        <div class="r-kpi-value r-kpi-moss" style="font-size:1.75rem; color:#059669;">${{ number_format($gananciaMes, 0, ',', '.') }}</div>
        <div class="r-kpi-label">Ganancia del Mes</div>
        @if($variacionGanancia != 0)
            <span style="font-size:0.75rem; color:{{ $variacionGanancia > 0 ? '#059669' : '#dc2626' }};">
                {{ $variacionGanancia > 0 ? '↑' : '↓' }} {{ number_format(abs($variacionGanancia), 1) }}% vs mes anterior
            </span>
        @endif
    </div>

    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.2">
        <div class="r-kpi-value r-kpi-marigold" style="font-size:1.75rem;">{{ number_format($margenMes, 1, ',', '.') }}%</div>
        <div class="r-kpi-label">Margen de Ganancia</div>
    </div>

    <div class="r-kpi" data-reveal="fade-up" data-reveal-delay="0.25">
        <div class="r-kpi-value" style="font-size:1.75rem;">${{ number_format($ticketPromedio, 0, ',', '.') }}</div>
        <div class="r-kpi-label">Ticket Promedio</div>
    </div>

</div>

{{-- KPIs Secundarios --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: var(--space-3);" class="r-mb-8">

    <div class="r-card r-text-center" data-reveal="scale" data-reveal-delay="0">
        <div class="r-kpi-value" style="font-size: 1.5rem;">{{ $totalProductos }}</div>
        <div class="r-kpi-label">Productos</div>
    </div>

    <div class="r-card r-text-center" data-reveal="scale" data-reveal-delay="0.03">
        <div class="r-kpi-value" style="font-size: 1.5rem;">{{ $totalClientes }}</div>
        <div class="r-kpi-label">Clientes</div>
    </div>

    <div class="r-card r-text-center" data-reveal="scale" data-reveal-delay="0.06">
        <div class="r-kpi-value" style="font-size: 1.5rem;">{{ $totalVentasMes }}</div>
        <div class="r-kpi-label">Ventas Mes</div>
    </div>

    <div class="r-card r-text-center" data-reveal="scale" data-reveal-delay="0.09">
        <div class="r-kpi-value" style="font-size: 1.5rem; {{ $stockCritico > 0 ? 'color: #dc2626;' : '' }}">{{ $stockCritico }}</div>
        <div class="r-kpi-label">Stock Crítico</div>
    </div>

    <div class="r-card r-text-center" data-reveal="scale" data-reveal-delay="0.12">
        <div class="r-kpi-value" style="font-size: 1.5rem;">{{ $totalUsuarios }}</div>
        <div class="r-kpi-label">Usuarios</div>
    </div>

</div>

{{-- Rhythm Divider --}}
<div class="r-divider" data-reveal="fade-up">
    <svg viewBox="0 0 120 24" width="120" height="24">
        <path class="r-divider-line" d="M0,12 Q10,4 20,12 T40,12 T60,12 T80,12 T100,12 T120,12" />
    </svg>
</div>

{{-- Top Productos Rentables --}}
@if($topRentables->count())
<div class="r-card-flat r-mb-8" data-reveal="fade-up" data-reveal-delay="0">
    <h3 class="r-body" style="font-weight:600; margin-bottom:var(--space-4);">
        <span style="display:inline-block; width:8px; height:8px; background:#059669; border-radius:50; margin-right:8px;"></span>
        Top Productos Más Rentables del Mes
    </h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th style="width:3rem; text-align:center;">#</th>
                    <th>Producto</th>
                    <th style="text-align:right;">Unidades</th>
                    <th style="text-align:right;">Ganancia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topRentables as $i => $prod)
                    <tr>
                        <td style="text-align:center; color:var(--color-ink-soft);">{{ $i + 1 }}</td>
                        <td style="font-weight:500;">{{ $prod->nombre }}</td>
                        <td style="text-align:right;">{{ $prod->vendidos }}</td>
                        <td style="text-align:right; font-weight:600; color:#059669;">${{ number_format($prod->ganancia, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Charts --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: var(--space-6);">

    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0">
        <h3 class="r-display-m" style="font-size: 1rem; margin-bottom: var(--space-5);">
            <span style="display: inline-block; width: 8px; height: 8px; background: var(--color-marigold); border-radius: 50; margin-right: 8px;"></span>
            Ventas Diarias
        </h3>
        <div style="height: 300px;">
            <canvas id="chartVentas"></canvas>
        </div>
    </div>

    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.08">
        <h3 class="r-display-m" style="font-size: 1rem; margin-bottom: var(--space-5);">
            <span style="display: inline-block; width: 8px; height: 8px; background: var(--color-moss); border-radius: 50; margin-right: 8px;"></span>
            Productos Más Vendidos
        </h3>
        <div style="height: 300px;">
            <canvas id="chartProductos"></canvas>
        </div>
    </div>

    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.16">
        <h3 class="r-display-m" style="font-size: 1rem; margin-bottom: var(--space-5);">
            <span style="display: inline-block; width: 8px; height: 8px; background: #dc2626; border-radius: 50; margin-right: 8px;"></span>
            Stock Crítico
        </h3>
        <div style="height: 300px;">
            <canvas id="chartStock"></canvas>
        </div>
    </div>

    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.24">
        <h3 class="r-display-m" style="font-size: 1rem; margin-bottom: var(--space-5);">
            <span style="display: inline-block; width: 8px; height: 8px; background: var(--color-marigold-deep); border-radius: 50; margin-right: 8px;"></span>
            Movimientos de Stock
        </h3>
        <div style="height: 300px;">
            <canvas id="chartMovimientos"></canvas>
        </div>
    </div>

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = getComputedStyle(document.documentElement);
    const textColor = root.getPropertyValue('--color-ink-soft').trim();
    const gridColor = 'rgba(216, 205, 184, 0.3)';
    const marigold = root.getPropertyValue('--color-marigold').trim();
    const moss = root.getPropertyValue('--color-moss').trim();
    const ink = root.getPropertyValue('--color-ink').trim();

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;
    Chart.defaults.font.family = "'Space Grotesk', sans-serif";

    // Ventas diarias
    const ventasData = @json($chartVentasDiarias);
    new Chart(document.getElementById('chartVentas'), {
        type: 'line',
        data: {
            labels: ventasData.map(d => { const f = new Date(d.fecha); return f.toLocaleDateString('es', {day:'2-digit', month:'short'}); }),
            datasets: [{
                label: 'Ventas ($)',
                data: ventasData.map(d => d.total),
                borderColor: marigold,
                backgroundColor: (ctx) => {
                    const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(226,161,59,0.15)');
                    gradient.addColorStop(1, 'rgba(226,161,59,0.0)');
                    return gradient;
                },
                fill: true,
                tension: 0.45,
                pointRadius: 5,
                pointBackgroundColor: marigold,
                pointBorderColor: '#F7F2E8',
                pointBorderWidth: 2.5,
                pointHoverRadius: 8,
                borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { padding: 10 } },
                x: { grid: { display: false }, ticks: { padding: 10 } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });

    // Top productos
    const productosData = @json($chartTopProductos);
    new Chart(document.getElementById('chartProductos'), {
        type: 'bar',
        data: {
            labels: productosData.map(d => d.nombre.length > 18 ? d.nombre.substring(0, 18) + '...' : d.nombre),
            datasets: [{
                label: 'Unidades',
                data: productosData.map(d => d.cantidad),
                backgroundColor: [moss, 'rgba(91,110,82,0.7)', 'rgba(91,110,82,0.55)', 'rgba(91,110,82,0.4)', 'rgba(91,110,82,0.25)'],
                borderRadius: 12,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor }, ticks: { padding: 10 } },
                y: { grid: { display: false }, ticks: { padding: 10 } }
            }
        }
    });

    // Stock bajo
    const stockData = @json($chartStockBajo);
    new Chart(document.getElementById('chartStock'), {
        type: 'bar',
        data: {
            labels: stockData.map(d => d.nombre.length > 18 ? d.nombre.substring(0, 18) + '...' : d.nombre),
            datasets: [
                {
                    label: 'Actual',
                    data: stockData.map(d => d.stock),
                    backgroundColor: '#dc2626',
                    borderRadius: 10,
                    borderSkipped: false,
                },
                {
                    label: 'Mínimo',
                    data: stockData.map(d => d.stock_minimo),
                    backgroundColor: 'rgba(220,38,38,0.2)',
                    borderRadius: 10,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } } },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor }, ticks: { padding: 10 } },
                y: { grid: { display: false }, ticks: { padding: 10 } }
            }
        }
    });

    // Movimientos doughnut
    const movData = @json($chartMovimientosMes);
    const movLabels = movData.map(d => d.tipo.charAt(0).toUpperCase() + d.tipo.slice(1));
    const movColors = movData.map(d => {
        if (d.tipo === 'entrada') return moss;
        if (d.tipo === 'salida') return '#dc2626';
        if (d.tipo === 'ajuste') return marigold;
        return ink;
    });
    new Chart(document.getElementById('chartMovimientos'), {
        type: 'doughnut',
        data: {
            labels: movLabels,
            datasets: [{
                data: movData.map(d => d.cantidad),
                backgroundColor: movColors,
                borderWidth: 0,
                hoverOffset: 10,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } } }
        }
    });

    // Animated counters
    const counters = document.querySelectorAll('.counter-animate');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.target) || 0;
                if (target === 0) { counterObserver.unobserve(el); return; }
                const duration = 1800;
                const start = performance.now();
                const animate = (now) => {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 4);
                    const current = Math.round(eased * target);
                    const text = el.textContent;
                    if (text.startsWith('$')) {
                        el.textContent = '$' + current.toLocaleString('es');
                    } else {
                        el.textContent = current;
                    }
                    if (progress < 1) requestAnimationFrame(animate);
                };
                requestAnimationFrame(animate);
                counterObserver.unobserve(el);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObserver.observe(c));
});
</script>
@endsection
