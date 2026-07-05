@extends('layouts.app')

@section('page_title', 'Detalle de venta ' . $venta->numero)

@section('content')

<h2 class="r-display-l r-mb-8" data-reveal="fade-up">Detalle de venta</h2>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.05">
    <div class="r-flex r-justify-between r-items-center" style="flex-wrap:wrap; gap:var(--space-4);">
        <div>
            <span class="r-mono" style="font-size:0.8125rem; color:var(--color-ink-soft);">{{ $venta->numero }}</span>
            <div class="r-body r-mt-1">{{ $venta->fecha->format('d/m/Y') }}</div>
        </div>
        <div>
            @if($venta->estado === 'completada')
                <span class="r-tag r-tag-success">Completada</span>
            @elseif($venta->estado === 'pendiente')
                <span class="r-tag r-tag-marigold">Pendiente</span>
            @else
                <span class="r-tag r-tag-danger">Cancelada</span>
            @endif
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:var(--space-4);" class="r-mb-6">
    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.1">
        <span class="r-caption">Cliente</span>
        <div class="r-body r-mt-1" style="font-weight:500;">{{ $venta->cliente->nombre ?? '—' }} {{ $venta->cliente->apellido ?? '' }}</div>
        <div class="r-caption r-mt-1" style="color:var(--color-ink-soft);">{{ $venta->cliente->email ?? '—' }}</div>
        <div class="r-caption" style="color:var(--color-ink-soft);">{{ $venta->cliente->telefono ?? '—' }}</div>
    </div>
    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.15">
        <span class="r-caption">Vendedor</span>
        <div class="r-body r-mt-1" style="font-weight:500;">{{ $venta->user->name ?? '—' }}</div>
    </div>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.2">
    <h3 class="r-body" style="font-weight:600; margin-bottom:var(--space-4);">Productos</h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align:right;">Cantidad</th>
                    <th style="text-align:right;">Precio unitario</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venta->detalles as $detalle)
                    <tr>
                        <td style="font-weight:500;">{{ $detalle->producto->nombre ?? '—' }}</td>
                        <td style="text-align:right;">{{ $detalle->cantidad }}</td>
                        <td style="text-align:right;">${{ number_format($detalle->precio, 2, ',', '.') }}</td>
                        <td style="text-align:right;">${{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin detalles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.25">
    <h3 class="r-body" style="font-weight:600; margin-bottom:var(--space-4);">Resumen</h3>
    <div style="display:flex; flex-direction:column; gap:var(--space-3); max-width:320px; margin-left:auto;">
        <div class="r-flex r-justify-between">
            <span style="color:var(--color-ink-soft);">Subtotal</span>
            <span style="font-weight:500;">${{ number_format($venta->subtotal ?? $venta->total, 2, ',', '.') }}</span>
        </div>
        <div class="r-flex r-justify-between">
            <span style="color:var(--color-ink-soft);">Descuento</span>
            <span style="font-weight:500;">-${{ number_format($venta->descuento ?? 0, 2, ',', '.') }}</span>
        </div>
        <div class="r-flex r-justify-between">
            <span style="color:var(--color-ink-soft);">Impuesto (IVA)</span>
            <span style="font-weight:500;">${{ number_format($venta->impuesto ?? 0, 2, ',', '.') }}</span>
        </div>
        <div style="border-top:1px solid var(--color-border); padding-top:var(--space-3);" class="r-flex r-justify-between">
            <span class="r-body" style="font-weight:600;">Total final</span>
            <span class="r-kpi-value" style="font-size:1.25rem;">${{ number_format($venta->total_final ?? $venta->total, 2, ',', '.') }}</span>
        </div>
    </div>
</div>

@if($venta->pagos && $venta->pagos->count())
<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.3">
    <h3 class="r-body" style="font-weight:600; margin-bottom:var(--space-4);">Medios de pago</h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Método</th>
                    <th style="text-align:right;">Monto</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->pagos as $pago)
                    <tr>
                        <td>{{ $pago->metodoPago->nombre ?? '—' }}</td>
                        <td style="text-align:right;">${{ number_format($pago->monto, 2, ',', '.') }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $pago->referencia ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div data-reveal="fade-up" data-reveal-delay="0.35">
    <a href="{{ route('ventas.index') }}" class="r-btn r-btn-ghost">← Volver a ventas</a>
</div>

@endsection
