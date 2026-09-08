@extends('layouts.app')

@section('page_title', 'Recuento ' . $recuento->numero)

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-4">
    <div>
        <h1 class="r-display-m">Recuento {{ $recuento->numero }}</h1>
        <p class="r-caption" style="text-transform:none;letter-spacing:0;">
            {{ $recuento->deposito?->nombre }} ·
            @if($recuento->estado === 'aplicado')<span style="color:var(--color-forest,#3f5135);">aplicado {{ $recuento->aplicado_en?->format('d/m/y H:i') }}</span>
            @else abierto @endif
        </p>
    </div>
    <a href="{{ route('recuentos.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
</div>

@if($errors->any())
    <div class="r-flash-error r-mb-4"><ul style="list-style:disc;padding-left:1.2em;margin:0;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('recuentos.guardar', $recuento) }}" class="r-card-flat">
    @csrf @method('PUT')
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Producto</th><th style="text-align:right;">Sistema</th><th style="text-align:right;width:8rem;">Contado</th><th style="text-align:right;">Diferencia</th></tr></thead>
            <tbody>
            @foreach($recuento->detalles as $d)
                <tr>
                    <td>{{ $d->producto?->nombre }}</td>
                    <td style="text-align:right;">{{ $d->stock_sistema }}</td>
                    <td style="text-align:right;">
                        @if($recuento->estado === 'abierto')
                            <input type="number" min="0" name="contado[{{ $d->producto_id }}]" value="{{ $d->contado }}" class="r-input r-input-sm" style="width:6rem;text-align:right;">
                        @else
                            {{ $d->contado ?? '—' }}
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if($d->diferencia != 0)
                            <span class="r-tag {{ $d->diferencia < 0 ? 'r-tag-danger' : 'r-tag-success' }}">{{ $d->diferencia > 0 ? '+' : '' }}{{ $d->diferencia }}</span>
                        @else — @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($recuento->estado === 'abierto')
        <div class="r-flex r-justify-between r-mt-4" style="border-top:1px solid var(--color-line);padding-top:1rem;">
            <button class="r-btn r-btn-ghost">Guardar conteo</button>
        </div>
    @endif
</form>

@if($recuento->estado === 'abierto')
    <form method="POST" action="{{ route('recuentos.aplicar', $recuento) }}" class="r-mt-4"
          onsubmit="return confirm('Se ajustará el stock de todos los ítems contados a la cantidad contada. ¿Continuar?');">
        @csrf
        <button class="r-btn r-btn-primary">Aplicar recuento (ajustar stock)</button>
    </form>
@endif
@endsection
