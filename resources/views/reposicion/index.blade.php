@extends('layouts.app')

@section('page_title', 'Reposición sugerida')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Reposición sugerida</h1>
    <a href="{{ route('ordenes-compra.index') }}" class="r-btn r-btn-ghost r-btn-sm">Órdenes de compra</a>
</div>

<form method="GET" class="r-card-flat r-mb-6 r-flex r-gap-3" style="align-items:flex-end;flex-wrap:wrap;">
    <div><label class="r-label">Ventana de análisis (días)</label><input type="number" name="dias" value="{{ $dias }}" class="r-input" style="width:8rem;"></div>
    <div><label class="r-label">Cobertura objetivo (días)</label><input type="number" name="cobertura" value="{{ $coberturaObjetivo }}" class="r-input" style="width:8rem;"></div>
    <button class="r-btn r-btn-accent r-btn-sm">Recalcular</button>
</form>

@if($porProveedor->isEmpty())
    <div class="r-card-flat" style="text-align:center;padding:3rem;color:var(--color-ink-soft);">
        Ningún producto necesita reposición con estos parámetros.
    </div>
@endif

@foreach($porProveedor as $proveedor => $lineas)
    <div class="r-card-flat r-mb-6">
        <div class="r-flex r-justify-between r-items-center r-mb-4">
            <h2 class="r-label" style="margin:0;">{{ $proveedor }} <span style="color:var(--color-ink-soft);">· {{ $lineas->count() }} productos</span></h2>
        </div>

        @php $provId = $lineas->first()['producto']->proveedor_id; @endphp
        <form method="POST" action="{{ route('reposicion.generar') }}">
            @csrf
            <input type="hidden" name="proveedor_id" value="{{ $provId }}">
            <div style="overflow-x:auto;">
                <table class="r-table">
                    <thead><tr>
                        <th>Producto</th><th style="text-align:right;">Vendido</th><th style="text-align:right;">/día</th>
                        <th style="text-align:right;">Stock</th><th style="text-align:right;">Cobertura</th><th style="text-align:right;">Pedir</th>
                    </tr></thead>
                    <tbody>
                    @foreach($lineas as $i => $s)
                        <tr>
                            <td>
                                {{ $s['producto']->nombre }}
                                <input type="hidden" name="lineas[{{ $i }}][producto_id]" value="{{ $s['producto']->id }}">
                            </td>
                            <td style="text-align:right;">{{ rtrim(rtrim(number_format($s['vendido'],2),'0'),'.') }}</td>
                            <td style="text-align:right;">{{ number_format($s['por_dia'],2) }}</td>
                            <td style="text-align:right;">{{ $s['stock'] }}</td>
                            <td style="text-align:right;">
                                @if($s['cobertura'] === null)<span class="r-tag">s/venta</span>
                                @else <span class="r-tag {{ $s['cobertura'] < 7 ? 'r-tag-danger' : '' }}">{{ $s['cobertura'] }} d</span>@endif
                            </td>
                            <td style="text-align:right;">
                                <input type="number" min="0" name="lineas[{{ $i }}][cantidad]" value="{{ $s['sugerido'] }}" class="r-input r-input-sm" style="width:6rem;text-align:right;">
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($provId)
                <div class="r-flex r-justify-end r-mt-4">
                    <button class="r-btn r-btn-primary r-btn-sm">Generar orden de compra</button>
                </div>
            @else
                <p class="r-caption r-mt-3" style="text-transform:none;letter-spacing:0;">Asigná un proveedor a estos productos para generar la orden automáticamente.</p>
            @endif
        </form>
    </div>
@endforeach
@endsection
