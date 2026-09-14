@extends('layouts.app')

@section('page_title', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')

@section('content')
<div style="max-width: 32rem; margin: 0 auto;">

    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">
            {{ $categoria->exists ? 'Editar categoría' : 'Nueva categoría' }}
        </h1>
        <a href="{{ route('categorias.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
    </div>

    <form action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}"
          method="POST"
          class="r-card-flat r-stack">
        @csrf
        @if ($categoria->exists)
            @method('PUT')
        @endif

        <div class="r-field" style="margin:0;">
            <label for="nombre" class="r-label">Nombre <span class="r-req">*</span></label>
            <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $categoria->nombre) }}" required
                   class="r-input @error('nombre') is-invalid @enderror">
            @error('nombre') <p class="r-error">{{ $message }}</p> @enderror
        </div>

        <div class="r-field" style="margin:0;">
            <label for="descripcion" class="r-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="3" class="r-input">{{ old('descripcion', $categoria->descripcion) }}</textarea>
            @error('descripcion') <p class="r-error">{{ $message }}</p> @enderror
        </div>

        <label class="r-cluster" style="gap:var(--space-2);cursor:pointer;">
            <input type="checkbox" name="estado" id="estado" value="1"
                   style="width:18px;height:18px;accent-color:var(--color-marigold);"
                   {{ old('estado', $categoria->estado ?? true) ? 'checked' : '' }}>
            <span class="r-body" style="font-size:0.9rem;">Categoría activa</span>
        </label>

        <div class="r-cluster r-justify-end" style="border-top:1px solid var(--color-line);padding-top:var(--space-4);">
            <a href="{{ route('categorias.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button type="submit" class="r-btn r-btn-primary">
                {{ $categoria->exists ? 'Guardar cambios' : 'Crear categoría' }}
            </button>
        </div>
    </form>

</div>
@endsection
