@extends('layouts.app')

@section('page_title', 'Categorías')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Categorías</h2>
    @if (auth()->user()->role?->tienePermiso('categorias.crear'))
        <a href="{{ route('categorias.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva categoría</a>
    @endif
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('categorias.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Nombre de la categoría" class="r-input">
        </div>
        <div>
            <label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="" @selected($estadoFiltro === '')>Todos</option>
                <option value="activo" @selected($estadoFiltro === 'activo')>Activas</option>
                <option value="inactivo" @selected($estadoFiltro === 'inactivo')>Inactivas</option>
            </select>
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if ($buscar || $estadoFiltro)
            <a href="{{ route('categorias.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categorias as $categoria)
                    <tr>
                        <td style="font-weight:500;">{{ $categoria->nombre }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $categoria->descripcion ?? '—' }}</td>
                        <td>
                            @if ($categoria->estado)
                                <span class="r-tag r-tag-success">Activa</span>
                            @else
                                <span class="r-tag">Inactiva</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if (auth()->user()->role?->tienePermiso('categorias.editar'))
                                    <a href="{{ route('categorias.edit', $categoria) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if ($categoria->estado && auth()->user()->role?->tienePermiso('categorias.eliminar'))
                                    <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" style="display:inline;" onsubmit="return confirm('¿Desactivar esta categoría?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;">Desactivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No hay categorías para mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($categorias->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        {{ $categorias->links() }}
    </div>
@endif

@endsection
