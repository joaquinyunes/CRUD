@extends('layouts.app')

@section('page_title', 'Tareas')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Tareas</h2>
    <div class="r-flex r-gap-3">
        <div style="display:flex; background:var(--color-line); border-radius:var(--border-radius-pill); padding:2px;">
            <a href="{{ route('tareas.index', ['vista' => 'lista']) }}" class="r-btn r-btn-sm" style="border-radius:var(--border-radius-pill); {{ request('vista', 'lista') === 'lista' ? 'background:var(--color-ink); color:var(--color-paper);' : 'background:transparent; color:var(--color-ink-soft);' }}">Lista</a>
            <a href="{{ route('tareas.index', ['vista' => 'kanban']) }}" class="r-btn r-btn-sm" style="border-radius:var(--border-radius-pill); {{ request('vista') === 'kanban' ? 'background:var(--color-ink); color:var(--color-paper);' : 'background:transparent; color:var(--color-ink-soft);' }}">Kanban</a>
        </div>
        <a href="{{ route('tareas.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva tarea</a>
    </div>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('tareas.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <input type="hidden" name="vista" value="{{ request('vista', 'lista') }}">
        <div style="min-width:200px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Título de la tarea…" class="r-input">
        </div>
        <div>
            <label class="r-label">Estado</label>
            <select name="estado" class="r-select">
                <option value="">Todos</option>
                <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                <option value="en_progreso" {{ request('estado') === 'en_progreso' ? 'selected' : '' }}>En progreso</option>
                <option value="completada" {{ request('estado') === 'completada' ? 'selected' : '' }}>Completada</option>
            </select>
        </div>
        <div>
            <label class="r-label">Prioridad</label>
            <select name="prioridad" class="r-select">
                <option value="">Todas</option>
                <option value="alta" {{ request('prioridad') === 'alta' ? 'selected' : '' }}>Alta</option>
                <option value="media" {{ request('prioridad') === 'media' ? 'selected' : '' }}>Media</option>
                <option value="baja" {{ request('prioridad') === 'baja' ? 'selected' : '' }}>Baja</option>
            </select>
        </div>
        <div>
            <label class="r-label">Asignada a</label>
            <select name="asignada_a" class="r-select">
                <option value="">Todas</option>
                @foreach($usuarios as $usr)
                    <option value="{{ $usr->id }}" {{ request('asignada_a') == $usr->id ? 'selected' : '' }}>{{ $usr->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->hasAny(['buscar','estado','prioridad','asignada_a']))
            <a href="{{ route('tareas.index', ['vista' => request('vista', 'lista')]) }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th style="text-align:center;">Prioridad</th>
                    <th style="text-align:center;">Estado</th>
                    <th>Asignada a</th>
                    <th>Fecha límite</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tareas as $tarea)
                    <tr style="{{ $tarea->estaVencida ? 'background:rgba(220,38,38,0.04);' : '' }}">
                        <td>
                            <span style="font-weight:500;">{{ $tarea->titulo }}</span>
                            @if($tarea->descripcion)
                                <span class="r-mono" style="font-size:0.6875rem; color:var(--color-ink-soft); display:block; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $tarea->descripcion }}</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tarea->prioridad === 'alta')
                                <span class="r-tag r-tag-danger">Alta</span>
                            @elseif($tarea->prioridad === 'media')
                                <span class="r-tag r-tag-marigold">Media</span>
                            @else
                                <span class="r-tag">Baja</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($tarea->estado === 'completada')
                                <span class="r-tag r-tag-success">Completada</span>
                            @elseif($tarea->estado === 'en_progreso')
                                <span class="r-tag" style="background:rgba(79,70,229,0.1); color:#4f46e5;">En progreso</span>
                            @else
                                <span class="r-tag r-tag-marigold">Pendiente</span>
                            @endif
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $tarea->asignada->name ?? '—' }}</td>
                        <td style="{{ $tarea->estaVencida ? 'color:#dc2626; font-weight:600;' : 'color:var(--color-ink-soft);' }}">
                            {{ $tarea->fecha_limite ? $tarea->fecha_limite->format('d/m/Y') : '—' }}
                            @if($tarea->estaVencida)
                                <span class="r-mono" style="font-size:0.625rem; display:block;">Vencida</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if($tarea->estado !== 'completada')
                                    @if($tarea->estado === 'pendiente')
                                        <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'en_progreso']) }}">
                                            @csrf
                                            <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:var(--color-moss);">Iniciar</button>
                                        </form>
                                    @else
                                        <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'completada']) }}">
                                            @csrf
                                            <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#16a34a;">Completar</button>
                                        </form>
                                    @endif
                                @endif
                                <a href="{{ route('tareas.edit', $tarea) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                <form method="POST" action="{{ route('tareas.destroy', $tarea) }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;" onclick="return confirm('¿Eliminar esta tarea?')">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron tareas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($tareas->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $tareas->firstItem() }}–{{ $tareas->lastItem() }} de {{ $tareas->total() }} tareas</span>
        {{ $tareas->links() }}
    </div>
@endif

@endsection
