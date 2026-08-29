@extends('layouts.app')

@section('page_title', 'Transferir stock')

@section('content')
<div class="max-w-xl mx-auto py-6">
    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">Transferir stock entre depósitos</h1>
        <a href="{{ route('depositos.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
    </div>

    @if($errors->any())
        <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('depositos.transferir') }}" class="r-card-flat space-y-4">
        @csrf
        <div><label class="r-label">Producto *</label>
            <select name="producto_id" class="r-select" required>
                <option value="">— Seleccioná —</option>
                @foreach($productos as $p)
                    <option value="{{ $p->id }}" {{ old('producto_id') == $p->id ? 'selected' : '' }}>{{ $p->nombre }} (total {{ $p->stock }})</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="r-label">Origen *</label>
                <select name="origen_id" class="r-select" required>
                    @foreach($depositos as $d)<option value="{{ $d->id }}" {{ old('origen_id') == $d->id ? 'selected' : '' }}>{{ $d->nombre }}</option>@endforeach
                </select>
            </div>
            <div><label class="r-label">Destino *</label>
                <select name="destino_id" class="r-select" required>
                    @foreach($depositos as $d)<option value="{{ $d->id }}" {{ old('destino_id') == $d->id ? 'selected' : '' }}>{{ $d->nombre }}</option>@endforeach
                </select>
            </div>
        </div>
        <div><label class="r-label">Cantidad *</label>
            <input type="number" name="cantidad" min="1" class="r-input" required value="{{ old('cantidad') }}"></div>
        <div class="r-flex r-justify-end r-gap-3">
            <a href="{{ route('depositos.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button class="r-btn r-btn-primary">Transferir</button>
        </div>
    </form>
</div>
@endsection
