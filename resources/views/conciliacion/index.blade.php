@extends('layouts.app')

@section('page_title', 'Conciliación Mercado Pago')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Conciliación Mercado Pago</h1>
    <a href="{{ route('reportes.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Reportes</a>
</div>

<form method="GET" class="r-card-flat r-mb-6 r-flex r-gap-3" style="align-items:flex-end;flex-wrap:wrap;">
    <div><label class="r-label">Desde</label><input type="date" name="fecha_desde" value="{{ $desde }}" class="r-input"></div>
    <div><label class="r-label">Hasta</label><input type="date" name="fecha_hasta" value="{{ $hasta }}" class="r-input"></div>
    <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
</form>

<div class="r-flex r-gap-4 r-mb-6" style="flex-wrap:wrap;">
    <div class="r-card-flat" style="flex:1;min-width:150px;"><div class="r-caption">Bruto aprobado</div><div class="r-display-m">${{ number_format($totales['bruto'], 2) }}</div></div>
    <div class="r-card-flat" style="flex:1;min-width:150px;"><div class="r-caption">Comisiones</div><div class="r-display-m">${{ number_format($totales['comision'], 2) }}</div></div>
    <div class="r-card-flat" style="flex:1;min-width:150px;"><div class="r-caption">Neto a acreditar</div><div class="r-display-m">${{ number_format($totales['neto'], 2) }}</div></div>
    <div class="r-card-flat" style="flex:1;min-width:150px;"><div class="r-caption">Cobros sin venta</div><div class="r-display-m">{{ $totales['sin_venta'] }}</div></div>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Fecha</th><th>Ref</th><th>ID MP</th><th>Venta</th><th>Estado</th><th style="text-align:right;">Monto</th><th style="text-align:right;">Comisión</th><th style="text-align:right;">Neto</th><th>Acredita</th></tr></thead>
            <tbody>
            @forelse($pagos as $p)
                <tr>
                    <td class="r-caption">{{ $p->created_at->format('d/m/y H:i') }}</td>
                    <td class="r-caption">{{ $p->external_ref }}</td>
                    <td class="r-caption">{{ $p->external_id ?? '—' }}</td>
                    <td>{{ $p->venta?->numero ?? '—' }}</td>
                    <td>
                        @switch($p->estado)
                            @case('aprobado')<span class="r-tag r-tag-success">Aprobado</span>@break
                            @case('rechazado')<span class="r-tag r-tag-danger">Rechazado</span>@break
                            @case('pendiente')<span class="r-tag" style="background:#fef3c7;color:#b45309;">Pendiente</span>@break
                            @default<span class="r-tag">{{ $p->estado }}</span>
                        @endswitch
                    </td>
                    <td style="text-align:right;">${{ number_format($p->monto, 2) }}</td>
                    <td style="text-align:right;">{{ $p->comision !== null ? '$'.number_format($p->comision, 2) : '—' }}</td>
                    <td style="text-align:right;">{{ $p->neto_acreditado !== null ? '$'.number_format($p->neto_acreditado, 2) : '—' }}</td>
                    <td class="r-caption">{{ $p->fecha_acreditacion?->format('d/m/y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--color-ink-soft);">Sin cobros en el período.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($pagos->hasPages())<div class="r-mt-4">{{ $pagos->links() }}</div>@endif
</div>
@endsection
