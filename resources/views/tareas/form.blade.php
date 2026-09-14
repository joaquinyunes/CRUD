@extends('layouts.app')

@section('page_title', isset($tarea) ? 'Editar tarea' : 'Nueva tarea')

@section('content')
<div style="max-width: 44rem; margin: 0 auto;">

    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">{{ isset($tarea) ? 'Editar tarea' : 'Nueva tarea' }}</h1>
        <a href="{{ route('tareas.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
    </div>

    @if($errors->any())
        <div class="r-flash-error r-mb-4">
            <ul style="list-style:disc;margin:0;padding-left:1.2em;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ isset($tarea) ? route('tareas.update', $tarea) : route('tareas.store') }}"
          class="r-card-flat r-stack">
        @csrf
        @isset($tarea)
            @method('PUT')
        @endisset

        <div class="r-field" style="margin:0;">
            <label for="titulo" class="r-label">Título <span class="r-req">*</span></label>
            <input type="text" name="titulo" id="titulo" value="{{ old('titulo', $tarea->titulo ?? '') }}" required
                   class="r-input @error('titulo') is-invalid @enderror">
            @error('titulo') <p class="r-error">{{ $message }}</p> @enderror
        </div>

        <div class="r-field" style="margin:0;">
            <label for="descripcion" class="r-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="r-input">{{ old('descripcion', $tarea->descripcion ?? '') }}</textarea>
            @error('descripcion') <p class="r-error">{{ $message }}</p> @enderror
        </div>

        <div class="r-grid r-grid-3">
            <div class="r-field" style="margin:0;">
                <label for="prioridad" class="r-label">Prioridad <span class="r-req">*</span></label>
                <select name="prioridad" id="prioridad" class="r-select" required>
                    <option value="baja"  {{ old('prioridad', $tarea->prioridad ?? 'media') === 'baja'  ? 'selected' : '' }}>Baja</option>
                    <option value="media" {{ old('prioridad', $tarea->prioridad ?? 'media') === 'media' ? 'selected' : '' }}>Media</option>
                    <option value="alta"  {{ old('prioridad', $tarea->prioridad ?? '') === 'alta'  ? 'selected' : '' }}>Alta</option>
                </select>
                @error('prioridad') <p class="r-error">{{ $message }}</p> @enderror
            </div>

            <div class="r-field" style="margin:0;">
                <label for="estado" class="r-label">Estado <span class="r-req">*</span></label>
                <select name="estado" id="estado" class="r-select" required>
                    <option value="pendiente"   {{ old('estado', $tarea->estado ?? 'pendiente') === 'pendiente'   ? 'selected' : '' }}>Pendiente</option>
                    <option value="en_progreso" {{ old('estado', $tarea->estado ?? '') === 'en_progreso' ? 'selected' : '' }}>En progreso</option>
                    <option value="completada"  {{ old('estado', $tarea->estado ?? '') === 'completada'  ? 'selected' : '' }}>Completada</option>
                </select>
                @error('estado') <p class="r-error">{{ $message }}</p> @enderror
            </div>

            <div class="r-field" style="margin:0;">
                <label for="fecha_limite" class="r-label">Fecha límite</label>
                <input type="date" name="fecha_limite" id="fecha_limite"
                       value="{{ old('fecha_limite', isset($tarea) && $tarea->fecha_limite ? $tarea->fecha_limite->format('Y-m-d') : '') }}"
                       class="r-input">
                @error('fecha_limite') <p class="r-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="r-field" style="margin:0;">
            <label for="asignada_a" class="r-label">Asignar a</label>
            <select name="asignada_a" id="asignada_a" class="r-select">
                <option value="">Sin asignar</option>
                @foreach($usuarios as $usr)
                    <option value="{{ $usr->id }}" {{ old('asignada_a', $tarea->asignada_a ?? '') == $usr->id ? 'selected' : '' }}>
                        {{ $usr->name }}
                    </option>
                @endforeach
            </select>
            @error('asignada_a') <p class="r-error">{{ $message }}</p> @enderror
        </div>

        <div class="r-cluster r-justify-end" style="padding-top:var(--space-4);border-top:1px solid var(--color-line);">
            <a href="{{ route('tareas.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button type="submit" class="r-btn r-btn-primary">
                {{ isset($tarea) ? 'Guardar cambios' : 'Crear tarea' }}
            </button>
        </div>
    </form>

</div>
@endsection
