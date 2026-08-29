@extends('layouts.app')

@section('page_title', 'Depósitos')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Depósitos</h2>
    <div class="r-flex r-gap-3">
        @if(auth()->user()->role?->tienePermiso('depositos.gestionar') || auth()->user()->role?->tienePermiso('stock.ajustar'))
            <a href="{{ route('depositos.transferir.form') }}" class="r-btn r-btn-accent r-btn-sm">Transferir stock</a>
        @endif
        @if(auth()->user()->role?->tienePermiso('depositos.gestionar'))
            <a href="{{ route('depositos.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo depósito</a>
        @endif
    </div>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="r-card-flat">
    <table class="r-table">
        <thead><tr><th>Nombre</th><th>Dirección</th><th>Principal</th><th>Estado</th><th style="text-align:right;">Unidades</th><th></th></tr></thead>
        <tbody>
        @foreach($depositos as $d)
            <tr>
                <td style="font-weight:500;">{{ $d->nombre }}</td>
                <td style="color:var(--color-ink-soft);">{{ $d->direccion ?? '—' }}</td>
                <td>{{ $d->es_principal ? '✓' : '' }}</td>
                <td>@if($d->activo)<span class="r-tag r-tag-success">Activo</span>@else<span class="r-tag">Inactivo</span>@endif</td>
                <td style="text-align:right;">{{ number_format($totales[$d->id] ?? 0, 0, ',', '.') }}</td>
                <td style="text-align:right;">
                    <a href="{{ route('depositos.stock', $d) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Ver stock</a>
                    @if(auth()->user()->role?->tienePermiso('depositos.gestionar'))
                        <a href="{{ route('depositos.edit', $d) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Editar</a>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
