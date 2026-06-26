@extends('layouts.app')

@section('page_title', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')

@section('content')
<div class="py-6">
    <div class="max-w-lg mx-auto sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                {{ $categoria->exists ? 'Editar categoría' : 'Nueva categoría' }}
            </h1>
            <a href="{{ route('categorias.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                ← Volver
            </a>
        </div>

        <form action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}"
              method="POST"
              class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">

            @csrf
            @if ($categoria->exists)
                @method('PUT')
            @endif

            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $categoria->nombre) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                       required>
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $categoria->descripcion) }}</textarea>
                @error('descripcion')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="estado" id="estado" value="1"
                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                       {{ old('estado', $categoria->estado ?? true) ? 'checked' : '' }}>
                <label for="estado" class="text-sm text-gray-700 dark:text-gray-300">Categoría activa</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('categorias.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ $categoria->exists ? 'Guardar cambios' : 'Crear categoría' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
