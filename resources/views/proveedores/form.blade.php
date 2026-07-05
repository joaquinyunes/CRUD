@extends('layouts.app')

@section('page_title', isset($proveedor) ? 'Editar proveedor' : 'Nuevo proveedor')

@section('content')
<div class="r-mb-8" data-reveal="fade-up">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="r-flex r-items-center r-justify-between r-mb-6">
            <h1 class="r-display-m">
                {{ isset($proveedor) ? 'Editar proveedor' : 'Nuevo proveedor' }}
            </h1>
            <a href="{{ route('proveedores.index') }}" class="r-btn r-btn-ghost r-caption">
                &larr; Volver
            </a>
        </div>

        @if($errors->any())
            <div class="r-card-flat r-mb-4 r-body" style="border-left: 3px solid var(--r-color-danger);">
                <ul class="list-disc list-inside r-gap-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ isset($proveedor) ? route('proveedores.update', $proveedor) : route('proveedores.store') }}"
              class="r-card-flat r-mb-6">

            @csrf
            @isset($proveedor)
                @method('PUT')
            @endisset

            <div class="r-flex r-gap-4" style="flex-wrap: wrap;" data-reveal="fade-up">
                <div style="flex: 1; min-width: 100%;">
                    <label for="nombre" class="r-label">Nombre <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                    <input type="text" name="nombre" id="nombre"
                           value="{{ old('nombre', $proveedor->nombre ?? '') }}"
                           class="r-input"
                           required>
                    @error('nombre') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label for="cuit" class="r-label">CUIT</label>
                    <input type="text" name="cuit" id="cuit"
                           value="{{ old('cuit', $proveedor->cuit ?? '') }}"
                           class="r-input">
                    @error('cuit') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label for="telefono" class="r-label">Teléfono</label>
                    <input type="text" name="telefono" id="telefono"
                           value="{{ old('telefono', $proveedor->telefono ?? '') }}"
                           class="r-input">
                    @error('telefono') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label for="email" class="r-label">Email</label>
                    <input type="email" name="email" id="email"
                           value="{{ old('email', $proveedor->email ?? '') }}"
                           class="r-input">
                    @error('email') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <label for="direccion" class="r-label">Dirección</label>
                    <input type="text" name="direccion" id="direccion"
                           value="{{ old('direccion', $proveedor->direccion ?? '') }}"
                           class="r-input">
                    @error('direccion') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="r-flex r-items-center r-justify-between r-mt-6" style="border-top: 1px solid var(--r-color-border); padding-top: 1.5rem;">
                <a href="{{ route('proveedores.index') }}" class="r-btn r-btn-ghost r-caption">
                    Cancelar
                </a>
                <button type="submit" class="r-btn r-btn-primary">
                    {{ isset($proveedor) ? 'Guardar cambios' : 'Crear proveedor' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
