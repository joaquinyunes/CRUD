@extends('layouts.app')

@section('page_title', 'Libro IVA Ventas')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Libro IVA Ventas</h1>
    <a href="{{ route('reportes.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Reportes</a>
</div>

<form method="GET" class="r-card-flat r-mb-6 r-flex r-gap-3" style="align-items:flex-end;flex-wrap:wrap;">
    <div><label class="r-label">Desde</label><input type="date" name="fecha_desde" value="{{ $desde }}" class="r-input"></div>
    <div><label class="r-label">Hasta</label><input type="date" name="fecha_hasta" value="{{ $hasta }}" class="r-input"></div>
    <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
</form>

<div class="r-flex r-gap-4 r-mb-6" style="flex-wrap:wrap;">
    <div class="r-card-flat" style="flex:1;min-width:160px;"><div class="r-caption">Neto gravado</div><div class="r-display-m">${{ number_format($totales['neto'], 2) }}</div></div>
    <div class="r-card-flat" style="flex:1;min-width:160px;"><div class="r-caption">IVA débito fiscal</div><div class="r-display-m">${{ number_format($totales['iva'], 2) }}</div></div>
    <div class="r-card-flat" style="flex:1;min-width:160px;"><div class="r-caption">Total facturado</div><div class="r-display-m">${{ number_format($totales['total'], 2) }}</div></div>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Fecha</th><th>Comprobante</th><th>Cliente</th><th>Doc</th><th style="text-align:right;">Neto</th><th style="text-align:right;">IVA</th><th style="text-align:right;">Total</th><th>CAE</th></tr></thead>
            <tbody>
            @forelse($comprobantes as $c)
                <tr>
                    <td class="r-caption">{{ $c->created_at->format('d/m/y') }}</td>
                    <td style="font-weight:500;">{{ $c->numeroFormateado() }}</td>
                    <td>{{ $c->venta?->cliente ? trim($c->venta->cliente->nombre.' '.$c->venta->cliente->apellido) : 'Consumidor final' }}</td>
                    <td class="r-caption">{{ $c->doc_nro !== '0' ? $c->doc_nro : '—' }}</td>
                    <td style="text-align:right;">${{ number_format($c->importe_neto, 2) }}</td>
                    <td style="text-align:right;">${{ number_format($c->importe_iva, 2) }}</td>
                    <td style="text-align:right;">${{ number_format($c->importe_total, 2) }}</td>
                    <td class="r-caption">{{ $c->cae }} @if($c->resultado === 'simulado')<span class="r-tag">sim</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--color-ink-soft);">Sin comprobantes en el período.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
