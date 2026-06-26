@extends('layouts.app')

@section('page_title', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('content')
<div class="py-6">
    <div class="max-w-lg mx-auto sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                {{ $cliente->exists ? 'Editar cliente' : 'Nuevo cliente' }}
            </h1>
            <a href="{{ route('clientes.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Volver
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/40 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}"
              class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 space-y-4">
            @csrf
            @if ($cliente->exists)
                @method('PUT')
            @endif

            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('nombre') border-red-500 @enderror">
                @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="apellido" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apellido <span class="text-red-500">*</span></label>
                <input type="text" name="apellido" id="apellido" value="{{ old('apellido', $cliente->apellido) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('apellido') border-red-500 @enderror">
                @error('apellido') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="documento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Documento</label>
                <input type="text" name="documento" id="documento" value="{{ old('documento', $cliente->documento) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('documento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $cliente->email) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('telefono') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección</label>
                <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $cliente->direccion) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('direccion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="observaciones" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('observaciones', $cliente->observaciones) }}</textarea>
                @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado <span class="text-red-500">*</span></label>
                <select name="estado" id="estado" required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="activo" @selected(old('estado', $cliente->estado ?? 'activo') === 'activo')>Activo</option>
                    <option value="archivado" @selected(old('estado', $cliente->estado) === 'archivado')>Archivado</option>
                </select>
                @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('clientes.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ $cliente->exists ? 'Guardar cambios' : 'Crear cliente' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
