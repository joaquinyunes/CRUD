@extends('layouts.app')

@section('page_title', 'Tareas — Kanban')

@section('content')

@php
    $bordePrioridad = fn($p) => match($p) {
        'alta' => 'var(--color-danger)',
        'media' => 'var(--color-marigold)',
        default => 'var(--color-line-dark)',
    };
@endphp

<div class="r-head">
    <h1 class="r-display-m">Tareas</h1>
    <div class="r-cluster">
        <span class="r-cluster" style="gap:2px;background:var(--color-bg-muted);border-radius:var(--border-radius-sm);padding:3px;">
            <a href="{{ route('tareas.index', ['vista' => 'lista']) }}" class="r-btn r-btn-sm" style="background:transparent;color:var(--color-ink-soft);">Lista</a>
            <a href="{{ route('tareas.index', ['vista' => 'kanban']) }}" class="r-btn r-btn-sm" style="background:var(--color-white);box-shadow:var(--shadow-sm);">Kanban</a>
        </span>
        <a href="{{ route('tareas.create') }}" class="r-btn r-btn-primary r-btn-sm">Nueva tarea</a>
    </div>
</div>

@if (session('success'))
    <div class="r-flash-success r-mb-6">{{ session('success') }}</div>
@endif

<div class="r-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));align-items:start;">
    @foreach ([
        ['titulo' => 'Pendientes',  'items' => $pendientes,  'tono' => 'var(--color-marigold-deep)'],
        ['titulo' => 'En progreso', 'items' => $enProgreso,  'tono' => '#2563EB'],
        ['titulo' => 'Completadas', 'items' => $completadas, 'tono' => 'var(--color-success)'],
    ] as $col)
        <div class="r-card-flat" style="padding:var(--space-4);">
            <h2 class="r-label r-cluster" style="gap:var(--space-2);margin-bottom:var(--space-4);color:{{ $col['tono'] }};">
                <span class="r-mono" style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:999px;background:var(--color-bg-muted);color:var(--color-ink);font-size:0.7rem;">{{ $col['items']->count() }}</span>
                {{ $col['titulo'] }}
            </h2>

            <div class="r-stack" style="gap:var(--space-3);{{ $col['titulo'] === 'Completadas' ? 'max-height:600px;overflow-y:auto;' : '' }}">
                @forelse($col['items'] as $tarea)
                    @php $completa = $tarea->estado === 'completada'; @endphp
                    <div style="padding:var(--space-3);background:var(--color-paper);border:1px solid var(--color-line);border-left:3px solid {{ $completa ? 'var(--color-line-dark)' : $bordePrioridad($tarea->prioridad) }};border-radius:var(--border-radius-sm);{{ $completa ? 'opacity:.7;' : '' }}">
                        <p style="margin:0;font-size:0.9rem;font-weight:500;color:{{ $completa ? 'var(--color-ink-soft)' : 'var(--color-ink)' }};{{ $completa ? 'text-decoration:line-through;' : '' }}">{{ $tarea->titulo }}</p>

                        @if($tarea->asignada)
                            <p class="r-caption" style="text-transform:none;letter-spacing:0;margin:4px 0 0;">{{ $tarea->asignada->name }}</p>
                        @endif

                        @if($tarea->fecha_limite && !$completa)
                            <p class="r-mono" style="font-size:0.7rem;margin:4px 0 0;color:{{ $tarea->estaVencida ? 'var(--color-danger)' : 'var(--color-ink-soft)' }};{{ $tarea->estaVencida ? 'font-weight:700;' : '' }}">
                                {{ $tarea->fecha_limite->format('d/m/Y') }}{{ $tarea->estaVencida ? ' · vencida' : '' }}
                            </p>
                        @endif

                        <div class="r-cluster" style="gap:var(--space-3);margin-top:var(--space-2);">
                            @if($tarea->estado === 'pendiente')
                                <form method="POST" action="{{ route('tareas.cambiar-estado', [$tarea, 'en_progreso']) }}">@csrf @method('PATCH')
                                    <button type="submit" class="r-link-btn">Empezar &rarr;</button>
                                </form>
                                <a href="{{ route('tareas.edit', $tarea) }}" class="r-link-btn" style="color:var(--color-ink-soft);">Editar</a>
                            @elseif($tarea->estado === 'en_progreso')
                                <form method="POST" action="{{ route('tareas.cambiar-estado', [$tarea, 'pendiente']) }}">@csrf @method('PATCH')
                                    <button type="submit" class="r-link-btn" style="color:var(--color-ink-soft);">&larr; Pendiente</button>
                                </form>
                                <form method="POST" action="{{ route('tareas.cambiar-estado', [$tarea, 'completada']) }}">@csrf @method('PATCH')
                                    <button type="submit" class="r-link-btn" style="color:var(--color-success);">Completar &check;</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('tareas.cambiar-estado', [$tarea, 'pendiente']) }}">@csrf @method('PATCH')
                                    <button type="submit" class="r-link-btn" style="color:var(--color-ink-soft);">Reabrir</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="r-caption" style="text-align:center;padding:var(--space-6) 0;text-transform:none;letter-spacing:0;">Sin tareas</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
@endsection
