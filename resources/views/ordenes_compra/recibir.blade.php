@extends('layouts.app')

@section('page_title', 'Recepción · ' . $orden->numero)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Recepción · Orden {{ $orden->numero }}</h2>
    <a href="{{ route('ordenes-compra.show', $orden) }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('ordenes-compra.recibir', $orden) }}">
    @csrf
    <div class="r-card-flat">
        <p class="r-caption r-mb-4">Ingresá cuánto llegó de cada ítem. Podés hacer recepciones parciales; el stock se suma al confirmar.</p>
        <table class="r-table">
            <thead><tr><th>Producto</th><th style="text-align:right;">Pedido</th><th style="text-align:right;">Ya recibido</th><th style="text-align:right;">Pendiente</th><th style="text-align:right;">Recibir ahora</th></tr></thead>
            <tbody>
            @foreach($orden->detalles as $d)
                <tr>
                    <td>{{ $d->producto->nombre ?? '—' }}</td>
                    <td style="text-align:right;">{{ $d->cantidad }}</td>
                    <td style="text-align:right;">{{ $d->cantidad_recibida }}</td>
                    <td style="text-align:right;">{{ $d->pendiente() }}</td>
                    <td style="text-align:right;">
                        <input type="number" name="recibido[{{ $d->id }}]" value="0" min="0" max="{{ $d->pendiente() }}"
                               class="r-input" style="width:100px; text-align:right;" {{ $d->pendiente() <= 0 ? 'disabled' : '' }}>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="r-flex r-justify-end r-mt-6">
        <button class="r-btn r-btn-primary">Confirmar recepción</button>
    </div>
</form>
@endsection
