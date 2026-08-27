@extends('layouts.app')

@section('page_title', 'Devolución ' . $devolucion->numero)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Devolución {{ $devolucion->numero }}</h2>
    <a href="{{ route('devoluciones.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

<div class="r-card-flat r-mb-6">
    <p><strong>Tipo:</strong> {{ $devolucion->tipo === 'venta' ? 'Devolución de venta' : 'Devolución a proveedor' }}</p>
    <p><strong>Documento:</strong> {{ $devolucion->venta->numero ?? $devolucion->compra->numero ?? '—' }}</p>
    <p><strong>Fecha:</strong> {{ $devolucion->fecha->format('d/m/Y') }}</p>
    <p><strong>Motivo:</strong> {{ $devolucion->motivo ?? '—' }}</p>
    <p><strong>Registró:</strong> {{ $devolucion->user->name ?? '—' }}</p>
</div>

<div class="r-card-flat">
    <table class="r-table">
        <thead><tr><th>Producto</th><th style="text-align:right;">Cantidad</th><th style="text-align:right;">Precio</th><th style="text-align:right;">Subtotal</th></tr></thead>
        <tbody>
        @foreach($devolucion->detalles as $d)
            <tr>
                <td>{{ $d->producto->nombre ?? '—' }}</td>
                <td style="text-align:right;">{{ $d->cantidad }}</td>
                <td style="text-align:right;">${{ number_format($d->precio, 2, ',', '.') }}</td>
                <td style="text-align:right;">${{ number_format($d->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><th colspan="3" style="text-align:right;">Total</th><th style="text-align:right;">${{ number_format($devolucion->total, 2, ',', '.') }}</th></tr></tfoot>
    </table>
</div>
@endsection
