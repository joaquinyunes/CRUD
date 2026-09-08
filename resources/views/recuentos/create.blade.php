@extends('layouts.app')

@section('page_title', 'Nuevo recuento')

@section('content')
<div style="max-width:32rem;margin:0 auto;">
    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">Nuevo recuento</h1>
        <a href="{{ route('recuentos.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
    </div>

    <form method="POST" action="{{ route('recuentos.store') }}" class="r-card-flat r-stack">
        @csrf
        <div class="r-field" style="margin:0;">
            <label class="r-label">Depósito</label>
            <select name="deposito_id" class="r-select" required>
                @foreach($depositos as $d)<option value="{{ $d->id }}">{{ $d->nombre }}</option>@endforeach
            </select>
        </div>
        <div class="r-field" style="margin:0;">
            <label class="r-label">Categoría (opcional — para conteo cíclico)</label>
            <select name="categoria_id" class="r-select">
                <option value="">Todo el catálogo</option>
                @foreach($categorias as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach
            </select>
        </div>
        <div class="r-field" style="margin:0;">
            <label class="r-label">Observaciones</label>
            <input type="text" name="observaciones" class="r-input">
        </div>
        <div class="r-cluster r-justify-end" style="border-top:1px solid var(--color-line);padding-top:var(--space-4);">
            <button class="r-btn r-btn-primary">Crear recuento</button>
        </div>
    </form>
</div>
@endsection
