@extends('layouts.app')

@section('page_title', 'Listas de precio')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Listas de precio</h2>
    <a href="{{ route('promociones.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Promociones</a>
</div>

@if($errors->any())
    <div class="r-flash-error r-mb-4"><ul style="list-style:disc;padding-left:1.2em;margin:0;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<p class="r-body r-mb-4" style="color:var(--color-ink-soft);max-width:44rem;">
    La lista base es el <strong>precio de venta</strong> de cada producto. Cada lista adicional
    aplica un ajuste porcentual sobre ese precio (podés asignar una lista a cada cliente).
</p>

<div class="r-card-flat r-mb-6" data-reveal="fade-up">
    <h3 class="r-label r-mb-3">Nueva lista</h3>
    <form method="POST" action="{{ route('listas-precio.store') }}" class="r-flex r-gap-3" style="flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div style="flex:1;min-width:180px;">
            <label class="r-label">Nombre</label>
            <input type="text" name="nombre" class="r-input" placeholder="Mayorista, Lista 2…" required>
        </div>
        <div style="width:9rem;">
            <label class="r-label">Ajuste %</label>
            <input type="number" step="0.01" name="ajuste_pct" class="r-input" value="0" required>
        </div>
        <div style="width:6rem;">
            <label class="r-label">Orden</label>
            <input type="number" name="orden" class="r-input" value="0">
        </div>
        <label class="r-cluster" style="gap:6px;cursor:pointer;padding-bottom:0.5rem;">
            <input type="checkbox" name="activa" value="1" checked><span class="r-body" style="font-size:0.85rem;">Activa</span>
        </label>
        <button class="r-btn r-btn-primary r-btn-sm">Agregar</button>
    </form>
</div>

<div class="r-stack" data-reveal="fade-up">
    @forelse($listas as $l)
        <div class="r-card-flat">
            <form method="POST" action="{{ route('listas-precio.update', $l) }}" class="r-flex r-gap-3" style="flex-wrap:wrap;align-items:flex-end;">
                @csrf @method('PUT')
                <div style="flex:1;min-width:160px;">
                    <label class="r-label">Nombre</label>
                    <input type="text" name="nombre" value="{{ $l->nombre }}" class="r-input">
                </div>
                <div style="width:8rem;">
                    <label class="r-label">Ajuste %</label>
                    <input type="number" step="0.01" name="ajuste_pct" value="{{ $l->ajuste_pct }}" class="r-input">
                </div>
                <div style="width:5rem;">
                    <label class="r-label">Orden</label>
                    <input type="number" name="orden" value="{{ $l->orden }}" class="r-input">
                </div>
                <label class="r-cluster" style="gap:6px;padding-bottom:0.5rem;">
                    <input type="checkbox" name="activa" value="1" @checked($l->activa)><span style="font-size:0.85rem;">Activa</span>
                </label>
                <span class="r-caption" style="text-transform:none;letter-spacing:0;padding-bottom:0.6rem;">{{ $l->precios_count }} precios propios</span>
                <button class="r-btn r-btn-ghost r-btn-sm">Guardar</button>
            </form>
            <form method="POST" action="{{ route('listas-precio.destroy', $l) }}" onsubmit="return confirm('¿Eliminar la lista?');" class="r-mt-2">
                @csrf @method('DELETE')
                <button class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem;color:#dc2626;">Eliminar lista</button>
            </form>
        </div>
    @empty
        <p style="color:var(--color-ink-soft);">Todavía no hay listas adicionales.</p>
    @endforelse
</div>
@endsection
