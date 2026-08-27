@extends('layouts.app')

@section('page_title', 'Devolución a proveedor · Compra ' . $compra->numero)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Devolución · Compra {{ $compra->numero }}</h2>
    <a href="{{ route('compras.show', $compra) }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('devoluciones.compra.store', $compra) }}">
    @csrf
    <div class="r-card-flat r-mb-6">
        <label class="r-label">Motivo</label>
        <input type="text" name="motivo" class="r-input" placeholder="Mercadería en mal estado, error de pedido…">
    </div>

    <div class="r-card-flat">
        <table class="r-table">
            <thead><tr><th>Producto</th><th style="text-align:right;">Comprado</th><th style="text-align:right;">Ya devuelto</th><th style="text-align:right;">Devolver ahora</th></tr></thead>
            <tbody>
            @foreach($compra->detalles as $i => $det)
                @php $yaDev = $devueltos[$det->producto_id] ?? 0; $max = $det->cantidad - $yaDev; @endphp
                <tr>
                    <td>
                        {{ $det->producto->nombre ?? '—' }}
                        <input type="hidden" name="detalles[{{ $i }}][producto_id]" value="{{ $det->producto_id }}">
                    </td>
                    <td style="text-align:right;">{{ $det->cantidad }}</td>
                    <td style="text-align:right;">{{ $yaDev }}</td>
                    <td style="text-align:right;">
                        <input type="number" name="detalles[{{ $i }}][cantidad]" value="0" min="0" max="{{ $max }}"
                               class="r-input" style="width:90px; text-align:right;" {{ $max <= 0 ? 'disabled' : '' }}>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="r-caption r-mt-3">Se descuenta del stock. No podés devolver más de lo que queda disponible.</p>
    </div>

    <div class="r-flex r-justify-end r-mt-6">
        <button class="r-btn r-btn-primary">Registrar devolución</button>
    </div>
</form>
@endsection
