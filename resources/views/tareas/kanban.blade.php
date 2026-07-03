@extends('layouts.app')

@section('page_title', 'Tareas — Kanban')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Tareas — Kanban</h1>

            <div class="flex items-center gap-2">
                <div class="flex bg-gray-200 dark:bg-gray-700 rounded-md p-0.5">
                    <a href="{{ route('tareas.index', ['vista' => 'lista']) }}"
                       class="px-3 py-1 text-sm rounded-md text-gray-500 dark:text-gray-400">
                        Lista
                    </a>
                    <a href="{{ route('tareas.index', ['vista' => 'kanban']) }}"
                       class="px-3 py-1 text-sm rounded-md bg-white dark:bg-gray-600 shadow text-gray-800 dark:text-gray-100">
                        Kanban
                    </a>
                </div>

                <a href="{{ route('tareas.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nueva tarea
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-yellow-700 dark:text-yellow-400 mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 dark:bg-yellow-900/50 text-xs">{{ $pendientes->count() }}</span>
                    Pendientes
                </h2>
                <div class="space-y-3">
                    @forelse($pendientes as $tarea)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border-l-4 {{ $tarea->prioridad === 'alta' ? 'border-red-500' : ($tarea->prioridad === 'media' ? 'border-yellow-500' : 'border-gray-300') }}">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $tarea->titulo }}</p>
                            @if($tarea->asignada)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $tarea->asignada->name }}</p>
                            @endif
                            @if($tarea->fecha_limite)
                                <p class="text-xs {{ $tarea->estaVencida ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-400' }} mt-1">
                                    {{ $tarea->fecha_limite->format('d/m/Y') }}{{ $tarea->estaVencida ? ' — VENCIDA' : '' }}
                                </p>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'en_progreso']) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:underline">→ En progreso</button>
                                </form>
                                <a href="{{ route('tareas.edit', $tarea) }}" class="text-xs text-gray-500 hover:underline">Editar</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">Sin tareas pendientes</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-blue-700 dark:text-blue-400 mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/50 text-xs">{{ $enProgreso->count() }}</span>
                    En progreso
                </h2>
                <div class="space-y-3">
                    @forelse($enProgreso as $tarea)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border-l-4 {{ $tarea->prioridad === 'alta' ? 'border-red-500' : ($tarea->prioridad === 'media' ? 'border-yellow-500' : 'border-gray-300') }}">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $tarea->titulo }}</p>
                            @if($tarea->asignada)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $tarea->asignada->name }}</p>
                            @endif
                            @if($tarea->fecha_limite)
                                <p class="text-xs {{ $tarea->estaVencida ? 'text-red-600 dark:text-red-400 font-bold' : 'text-gray-400' }} mt-1">
                                    {{ $tarea->fecha_limite->format('d/m/Y') }}{{ $tarea->estaVencida ? ' — VENCIDA' : '' }}
                                </p>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'pendiente']) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-yellow-600 hover:underline">← Pendiente</button>
                                </form>
                                <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'completada']) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-green-600 hover:underline">✓ Completar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">Sin tareas en progreso</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                <h2 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/50 text-xs">{{ $completadas->count() }}</span>
                    Completadas
                </h2>
                <div class="space-y-3 max-h-[600px] overflow-y-auto">
                    @forelse($completadas as $tarea)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg opacity-75">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 line-through">{{ $tarea->titulo }}</p>
                            @if($tarea->asignada)
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $tarea->asignada->name }}</p>
                            @endif
                            <div class="flex items-center gap-2 mt-2">
                                <form method="PATCH" action="{{ route('tareas.cambiar-estado', [$tarea, 'pendiente']) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-yellow-600 hover:underline">Reabrir</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">Sin tareas completadas</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</div>
@endsection
