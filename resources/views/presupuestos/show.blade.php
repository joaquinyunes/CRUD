@extends('layouts.app')

@section('page_title', 'Presupuesto ' . $presupuesto->numero)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Presupuesto {{ $presupuesto->numero }}</h2>
    <a href="{{ route('presupuestos.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="r-card-flat r-mb-6">
    <p><strong>Cliente:</strong> {{ $presupuesto->cliente->nombre }} {{ $presupuesto->cliente->apellido }}</p>
    <p><strong>Fecha:</strong> {{ $presupuesto->fecha->format('d/m/Y') }} · <strong>Validez:</strong> {{ $presupuesto->validez_dias }} días</p>
    <p><strong>Estado:</strong> {{ ucfirst($presupuesto->estado) }}</p>
    @if($presupuesto->venta)
        <p><strong>Convertido en venta:</strong> <a href="{{ route('ventas.show', $presupuesto->venta) }}">{{ $presupuesto->venta->numero }}</a></p>
    @endif
    @if($presupuesto->observaciones)<p><strong>Observaciones:</strong> {{ $presupuesto->observaciones }}</p>@endif
</div>

<div class="r-card-flat r-mb-6">
    <table class="r-table">
        <thead><tr><th>Producto</th><th style="text-align:right;">Cant.</th><th style="text-align:right;">Precio</th><th style="text-align:right;">Subtotal</th></tr></thead>
        <tbody>
        @foreach($presupuesto->detalles as $d)
            <tr><td>{{ $d->producto->nombre ?? '—' }}</td>
                <td style="text-align:right;">{{ $d->cantidad }}</td>
                <td style="text-align:right;">${{ number_format($d->precio, 2, ',', '.') }}</td>
                <td style="text-align:right;">${{ number_format($d->subtotal, 2, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><th colspan="3" style="text-align:right;">Subtotal</th><th style="text-align:right;">${{ number_format($presupuesto->subtotal, 2, ',', '.') }}</th></tr>
            <tr><th colspan="3" style="text-align:right;">Descuento</th><th style="text-align:right;">-${{ number_format($presupuesto->descuento, 2, ',', '.') }}</th></tr>
            <tr><th colspan="3" style="text-align:right;">Impuesto</th><th style="text-align:right;">${{ number_format($presupuesto->impuesto, 2, ',', '.') }}</th></tr>
            <tr><th colspan="3" style="text-align:right;">Total</th><th style="text-align:right;">${{ number_format($presupuesto->total, 2, ',', '.') }}</th></tr>
        </tfoot>
    </table>
</div>

<div class="r-flex r-gap-3">
    @if(!$presupuesto->venta_id && auth()->user()->role?->tienePermiso('presupuestos.editar'))
        <a href="{{ route('presupuestos.edit', $presupuesto) }}" class="r-btn r-btn-ghost">Editar</a>
    @endif
    @if($presupuesto->puedeConvertirse() && auth()->user()->role?->tienePermiso('presupuestos.convertir'))
        <form method="POST" action="{{ route('presupuestos.convertir', $presupuesto) }}" onsubmit="return confirm('¿Convertir en venta? Se creará una venta pendiente para revisar.');">
            @csrf
            <button class="r-btn r-btn-primary">Convertir en venta</button>
        </form>
    @endif
</div>
@endsection
