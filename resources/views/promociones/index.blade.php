@extends('layouts.app')

@section('page_title', 'Promociones')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Promociones</h2>
    <div class="r-flex r-gap-3">
        <a href="{{ route('listas-precio.index') }}" class="r-btn r-btn-ghost r-btn-sm">Listas de precio</a>
        <a href="{{ route('promociones.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva promoción</a>
    </div>
</div>

<div class="r-card-flat" data-reveal="fade-up">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr>
                <th>Nombre</th><th>Tipo</th><th>Aplica a</th><th>Vigencia</th><th>Prioridad</th><th>Estado</th><th style="text-align:right;">Acciones</th>
            </tr></thead>
            <tbody>
            @forelse($promociones as $p)
                <tr>
                    <td style="font-weight:500;">{{ $p->nombre }}</td>
                    <td>
                        @switch($p->tipo)
                            @case('porcentaje') {{ rtrim(rtrim(number_format($p->valor,2),'0'),'.') }}% @break
                            @case('monto') ${{ number_format($p->valor,2) }} c/u @break
                            @case('precio_fijo') Precio fijo ${{ number_format($p->valor,2) }} @break
                            @case('nxm') {{ $p->n }}x{{ $p->m }} @break
                        @endswitch
                    </td>
                    <td style="color:var(--color-ink-soft);">
                        @if($p->alcance === 'todos') Todos
                        @elseif($p->alcance === 'categoria') Cat: {{ $p->categoria?->nombre }}
                        @else {{ $p->producto?->nombre }}
                        @endif
                    </td>
                    <td style="color:var(--color-ink-soft);font-size:0.85rem;">
                        {{ $p->desde?->format('d/m/y') ?? '—' }} → {{ $p->hasta?->format('d/m/y') ?? '—' }}
                        @if($p->hora_desde) <br>{{ substr($p->hora_desde,0,5) }}–{{ substr($p->hora_hasta,0,5) }} @endif
                    </td>
                    <td>{{ $p->prioridad }}</td>
                    <td>@if($p->activa)<span class="r-tag r-tag-success">Activa</span>@else<span class="r-tag">Inactiva</span>@endif</td>
                    <td style="text-align:right;">
                        <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                            <a href="{{ route('promociones.edit', $p) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem;padding:4px 12px;">Editar</a>
                            <form method="POST" action="{{ route('promociones.destroy', $p) }}" onsubmit="return confirm('¿Eliminar la promoción?');">
                                @csrf @method('DELETE')
                                <button class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem;padding:4px 12px;color:#dc2626;">Eliminar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:var(--space-8);color:var(--color-ink-soft);">Sin promociones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($promociones->hasPages())
    <div class="r-mt-6">{{ $promociones->links() }}</div>
@endif
@endsection
