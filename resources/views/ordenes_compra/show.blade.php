@extends('layouts.app')

@section('page_title', 'Orden ' . $orden->numero)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Orden de compra {{ $orden->numero }}</h2>
    <a href="{{ route('ordenes-compra.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="r-card-flat r-mb-6">
    <p><strong>Proveedor:</strong> {{ $orden->proveedor->nombre ?? '—' }}</p>
    <p><strong>Fecha:</strong> {{ $orden->fecha->format('d/m/Y') }}
       @if($orden->fecha_entrega_estimada) · <strong>Entrega estimada:</strong> {{ $orden->fecha_entrega_estimada->format('d/m/Y') }}@endif</p>
    <p><strong>Estado:</strong> {{ ucfirst($orden->estado) }}</p>
    @if($orden->compra)<p><strong>Facturada como compra:</strong> <a href="{{ route('compras.show', $orden->compra) }}">{{ $orden->compra->numero }}</a></p>@endif
    @if($orden->observaciones)<p><strong>Observaciones:</strong> {{ $orden->observaciones }}</p>@endif
</div>

<div class="r-card-flat r-mb-6">
    <table class="r-table">
        <thead><tr><th>Producto</th><th style="text-align:right;">Pedido</th><th style="text-align:right;">Recibido</th><th style="text-align:right;">Pendiente</th><th style="text-align:right;">Precio</th><th style="text-align:right;">Subtotal</th></tr></thead>
        <tbody>
        @foreach($orden->detalles as $d)
            <tr>
                <td>{{ $d->producto->nombre ?? '—' }}</td>
                <td style="text-align:right;">{{ $d->cantidad }}</td>
                <td style="text-align:right;">{{ $d->cantidad_recibida }}</td>
                <td style="text-align:right; font-weight:600;">{{ $d->pendiente() }}</td>
                <td style="text-align:right;">${{ number_format($d->precio, 2, ',', '.') }}</td>
                <td style="text-align:right;">${{ number_format($d->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><th colspan="5" style="text-align:right;">Total</th><th style="text-align:right;">${{ number_format($orden->total, 2, ',', '.') }}</th></tr></tfoot>
    </table>
</div>

<div class="r-flex r-gap-3">
    @if(!in_array($orden->estado, ['recibida','cancelada']) && auth()->user()->role?->tienePermiso('ordenes_compra.recibir'))
        <a href="{{ route('ordenes-compra.recibir.form', $orden) }}" class="r-btn r-btn-primary">Registrar recepción</a>
    @endif
    @if(!$orden->compra_id && $orden->tieneRecepciones() && auth()->user()->role?->tienePermiso('ordenes_compra.recibir'))
        <form method="POST" action="{{ route('ordenes-compra.facturar', $orden) }}"
              onsubmit="return confirm('¿Generar la compra por lo recibido? Suma la deuda con el proveedor.');">
            @csrf
            <button class="r-btn r-btn-accent">Generar compra</button>
        </form>
    @endif
    @if(!$orden->tieneRecepciones() && !$orden->compra_id && auth()->user()->role?->tienePermiso('ordenes_compra.editar'))
        <a href="{{ route('ordenes-compra.edit', $orden) }}" class="r-btn r-btn-ghost">Editar</a>
    @endif
</div>
@endsection
