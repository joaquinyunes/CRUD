@extends('layouts.app')

@section('page_title', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('content')
<div class="r-mb-8" data-reveal="fade-up">
    <div class="max-w-lg mx-auto sm:px-6 lg:px-8">

        <div class="r-flex r-items-center r-justify-between r-mb-6">
            <h1 class="r-display-m">
                {{ $cliente->exists ? 'Editar cliente' : 'Nuevo cliente' }}
            </h1>
            <a href="{{ route('clientes.index') }}" class="r-btn r-btn-ghost r-caption">
                &larr; Volver
            </a>
        </div>

        @if ($errors->any())
            <div class="r-card-flat r-mb-4 r-body" style="border-left: 3px solid var(--r-color-danger);">
                <ul class="list-disc list-inside r-gap-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}"
              class="r-card-flat r-mb-6 r-gap-4">
            @csrf
            @if ($cliente->exists)
                @method('PUT')
            @endif

            <div data-reveal="fade-up">
                <label for="nombre" class="r-label">Nombre <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                       class="r-input @error('nombre') is-invalid @enderror">
                @error('nombre') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="apellido" class="r-label">Apellido <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                <input type="text" name="apellido" id="apellido" value="{{ old('apellido', $cliente->apellido) }}" required
                       class="r-input @error('apellido') is-invalid @enderror">
                @error('apellido') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="documento" class="r-label">Documento</label>
                <input type="text" name="documento" id="documento" value="{{ old('documento', $cliente->documento) }}"
                       class="r-input">
                @error('documento') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="r-label">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $cliente->email) }}"
                       class="r-input">
                @error('email') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="telefono" class="r-label">Teléfono</label>
                <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                       class="r-input">
                @error('telefono') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="direccion" class="r-label">Dirección</label>
                <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $cliente->direccion) }}"
                       class="r-input">
                @error('direccion') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="observaciones" class="r-label">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="3"
                          class="r-input">{{ old('observaciones', $cliente->observaciones) }}</textarea>
                @error('observaciones') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="estado" class="r-label">Estado <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                <select name="estado" id="estado" required class="r-select">
                    <option value="activo" @selected(old('estado', $cliente->estado ?? 'activo') === 'activo')>Activo</option>
                    <option value="archivado" @selected(old('estado', $cliente->estado) === 'archivado')>Archivado</option>
                </select>
                @error('estado') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div class="r-flex r-items-center r-justify-between r-mt-4" style="border-top: 1px solid var(--r-color-border); padding-top: 1rem;">
                <a href="{{ route('clientes.index') }}" class="r-btn r-btn-ghost r-caption">
                    Cancelar
                </a>
                <button type="submit" class="r-btn r-btn-primary">
                    {{ $cliente->exists ? 'Guardar cambios' : 'Crear cliente' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
