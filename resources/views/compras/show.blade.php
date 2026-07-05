@extends('layouts.app')

@section('page_title', 'Detalle de compra ' . $compra->numero)

@section('content')

<h2 class="r-display-l r-mb-8" data-reveal="fade-up">Detalle de compra</h2>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.05">
    <div class="r-flex r-justify-between r-items-center" style="flex-wrap:wrap; gap:var(--space-4);">
        <div>
            <span class="r-mono" style="font-size:0.8125rem; color:var(--color-ink-soft);">{{ $compra->numero }}</span>
            <div class="r-body r-mt-1">{{ $compra->fecha->format('d/m/Y') }}</div>
        </div>
        <div>
            @if($compra->estado === 'completada')
                <span class="r-tag r-tag-success">Completada</span>
            @elseif($compra->estado === 'pendiente')
                <span class="r-tag r-tag-marigold">Pendiente</span>
            @else
                <span class="r-tag r-tag-danger">Cancelada</span>
            @endif
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:var(--space-4);" class="r-mb-6">
    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.1">
        <span class="r-caption">Proveedor</span>
        <div class="r-body r-mt-1" style="font-weight:500;">{{ $compra->proveedor->nombre ?? '—' }}</div>
        <div class="r-caption r-mt-1" style="color:var(--color-ink-soft);">CUIT: {{ $compra->proveedor->cuit ?? '—' }}</div>
        <div class="r-caption" style="color:var(--color-ink-soft);">{{ $compra->proveedor->email ?? '—' }}</div>
        <div class="r-caption" style="color:var(--color-ink-soft);">{{ $compra->proveedor->telefono ?? '—' }}</div>
    </div>
    <div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.15">
        <span class="r-caption">Comprador</span>
        <div class="r-body r-mt-1" style="font-weight:500;">{{ $compra->user->name ?? '—' }}</div>
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
                @forelse($compra->detalles as $detalle)
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
    <div class="r-flex r-justify-between" style="max-width:320px; margin-left:auto;">
        <span class="r-body" style="font-weight:600;">Total</span>
        <span class="r-kpi-value" style="font-size:1.25rem;">${{ number_format($compra->total, 2, ',', '.') }}</span>
    </div>
</div>

<div data-reveal="fade-up" data-reveal-delay="0.3">
    <a href="{{ route('compras.index') }}" class="r-btn r-btn-ghost">← Volver a compras</a>
</div>

@endsection
