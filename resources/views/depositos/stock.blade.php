@extends('layouts.app')

@section('page_title', 'Stock · ' . $deposito->nombre)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Stock en {{ $deposito->nombre }}</h2>
    <a href="{{ route('depositos.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

<div class="r-card-flat r-mb-6">
    <form method="GET" class="r-flex r-gap-3" style="align-items:flex-end;">
        <div style="flex:1; max-width:320px;"><label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" class="r-input" placeholder="Nombre o código…"></div>
        <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
    </form>
</div>

<div class="r-card-flat">
    <table class="r-table">
        <thead><tr><th>Código</th><th>Producto</th><th style="text-align:right;">En este depósito</th><th style="text-align:right;">Total (todos)</th><th style="text-align:right;">Mínimo</th></tr></thead>
        <tbody>
        @forelse($productos as $p)
            @php $cant = $cantidades[$p->id] ?? 0; @endphp
            <tr>
                <td style="color:var(--color-ink-soft);">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td style="text-align:right; font-weight:600; color:{{ $cant <= 0 ? '#dc2626' : 'inherit' }};">{{ $cant }}</td>
                <td style="text-align:right;">{{ $p->stock }}</td>
                <td style="text-align:right; color:var(--color-ink-soft);">{{ $p->stock_minimo }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin productos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($productos->hasPages())<div class="r-mt-6">{{ $productos->links() }}</div>@endif
@endsection
