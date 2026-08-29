@extends('layouts.app')

@section('page_title', isset($deposito) ? 'Editar depósito' : 'Nuevo depósito')

@section('content')
<div class="max-w-xl mx-auto py-6">
    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">{{ isset($deposito) ? 'Editar depósito' : 'Nuevo depósito' }}</h1>
        <a href="{{ route('depositos.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
    </div>

    @if($errors->any())
        <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ isset($deposito) ? route('depositos.update', $deposito) : route('depositos.store') }}" class="r-card-flat space-y-4">
        @csrf
        @isset($deposito)@method('PUT')@endisset
        <div><label class="r-label">Nombre *</label>
            <input type="text" name="nombre" class="r-input" required value="{{ old('nombre', $deposito->nombre ?? '') }}"></div>
        <div><label class="r-label">Dirección</label>
            <input type="text" name="direccion" class="r-input" value="{{ old('direccion', $deposito->direccion ?? '') }}"></div>
        <label class="r-flex r-gap-2 r-items-center">
            <input type="checkbox" name="es_principal" value="1" {{ old('es_principal', $deposito->es_principal ?? false) ? 'checked' : '' }}>
            Depósito principal (destino por defecto de compras y ventas)
        </label>
        <div class="r-flex r-justify-end r-gap-3">
            <a href="{{ route('depositos.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button class="r-btn r-btn-primary">Guardar</button>
        </div>
    </form>

    @isset($deposito)
        @if(!$deposito->es_principal && auth()->user()->role?->tienePermiso('depositos.gestionar'))
            <form method="POST" action="{{ route('depositos.destroy', $deposito) }}" class="r-mt-4" onsubmit="return confirm('¿Desactivar este depósito?');">
                @csrf @method('DELETE')
                <button class="r-btn r-btn-ghost" style="color:#dc2626;">Desactivar depósito</button>
            </form>
        @endif
    @endisset
</div>
@endsection
