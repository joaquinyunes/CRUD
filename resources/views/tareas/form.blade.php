@extends('layouts.app')

@section('page_title', isset($tarea) ? 'Editar tarea' : 'Nueva tarea')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                {{ isset($tarea) ? 'Editar tarea' : 'Nueva tarea' }}
            </h1>
            <a href="{{ route('tareas.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Volver
            </a>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($tarea) ? route('tareas.update', $tarea) : route('tareas.store') }}"
              class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">

            @csrf
            @isset($tarea)
                @method('PUT')
            @endisset

            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título <span class="text-red-500">*</span></label>
                <input type="text" name="titulo" id="titulo"
                       value="{{ old('titulo', $tarea->titulo ?? '') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       required>
                @error('titulo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $tarea->descripcion ?? '') }}</textarea>
                @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="prioridad" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridad <span class="text-red-500">*</span></label>
                    <select name="prioridad" id="prioridad"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required>
                        <option value="baja"  {{ old('prioridad', $tarea->prioridad ?? 'media') === 'baja'  ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ old('prioridad', $tarea->prioridad ?? 'media') === 'media' ? 'selected' : '' }}>Media</option>
                        <option value="alta"  {{ old('prioridad', $tarea->prioridad ?? '') === 'alta'  ? 'selected' : '' }}>Alta</option>
                    </select>
                    @error('prioridad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado <span class="text-red-500">*</span></label>
                    <select name="estado" id="estado"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required>
                        <option value="pendiente"   {{ old('estado', $tarea->estado ?? 'pendiente') === 'pendiente'   ? 'selected' : '' }}>Pendiente</option>
                        <option value="en_progreso" {{ old('estado', $tarea->estado ?? '') === 'en_progreso' ? 'selected' : '' }}>En progreso</option>
                        <option value="completada"  {{ old('estado', $tarea->estado ?? '') === 'completada'  ? 'selected' : '' }}>Completada</option>
                    </select>
                    @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="fecha_limite" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha límite</label>
                    <input type="date" name="fecha_limite" id="fecha_limite"
                           value="{{ old('fecha_limite', isset($tarea) && $tarea->fecha_limite ? $tarea->fecha_limite->format('Y-m-d') : '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('fecha_limite') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="asignada_a" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a</label>
                <select name="asignada_a" id="asignada_a"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Sin asignar</option>
                    @foreach($usuarios as $usr)
                        <option value="{{ $usr->id }}"
                            {{ old('asignada_a', $tarea->asignada_a ?? '') == $usr->id ? 'selected' : '' }}>
                            {{ $usr->name }}
                        </option>
                    @endforeach
                </select>
                @error('asignada_a') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('tareas.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ isset($tarea) ? 'Guardar cambios' : 'Crear tarea' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
