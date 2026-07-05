@extends('layouts.app')

@section('page_title', 'Clientes')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Clientes</h2>
    @if (auth()->user()->role?->tienePermiso('clientes.crear'))
        <a href="{{ route('clientes.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo cliente</a>
    @endif
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('clientes.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Nombre, apellido o documento…" class="r-input">
        </div>
        <div>
            <label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="activo" @selected($estado === 'activo')>Activos</option>
                <option value="archivado" @selected($estado === 'archivado')>Archivados</option>
                <option value="todos" @selected($estado === 'todos')>Todos</option>
            </select>
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        <a href="{{ route('clientes.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Nombre y Apellido</th>
                    <th>Documento</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    <tr>
                        <td style="font-weight:500;">{{ $cliente->nombre }} {{ $cliente->apellido }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $cliente->documento ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $cliente->email ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $cliente->telefono ?? '—' }}</td>
                        <td>
                            @if ($cliente->estado === 'activo')
                                <span class="r-tag r-tag-success">Activo</span>
                            @else
                                <span class="r-tag">Archivado</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if (auth()->user()->role?->tienePermiso('clientes.editar'))
                                    <a href="{{ route('clientes.edit', $cliente) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if ($cliente->estado === 'activo' && auth()->user()->role?->tienePermiso('clientes.eliminar'))
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" style="display:inline;" onsubmit="return confirm('¿Archivar a {{ $cliente->nombre }} {{ $cliente->apellido }}?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;">Archivar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron clientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($clientes->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $clientes->firstItem() }}–{{ $clientes->lastItem() }} de {{ $clientes->total() }} clientes</span>
        {{ $clientes->links() }}
    </div>
@endif

@endsection
