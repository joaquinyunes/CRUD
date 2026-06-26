@extends('layouts.app')

@section('page_title', isset($producto) ? 'Editar producto' : 'Nuevo producto')

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                {{ isset($producto) ? 'Editar producto' : 'Nuevo producto' }}
            </h1>
            <a href="{{ route('productos.index') }}"
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
              action="{{ isset($producto) ? route('productos.update', $producto) : route('productos.store') }}"
              enctype="multipart/form-data"
              class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            @csrf
            @isset($producto)
                @method('PUT')
            @endisset

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Columna izquierda: campos principales --}}
                <div class="lg:col-span-2 space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código <span class="text-red-500">*</span></label>
                            <input type="text" name="codigo" id="codigo"
                                   value="{{ old('codigo', $producto->codigo ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('codigo') border-red-500 @enderror"
                                   required>
                            @error('codigo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre <span class="text-red-500">*</span></label>
                            <input type="text" name="nombre" id="nombre"
                                   value="{{ old('nombre', $producto->nombre ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('nombre') border-red-500 @enderror"
                                   required>
                            @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                        @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="categoria_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría <span class="text-red-500">*</span></label>
                            <select name="categoria_id" id="categoria_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('categoria_id') border-red-500 @enderror"
                                    required>
                                <option value="">— Seleccioná una categoría —</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('categoria_id', $producto->categoria_id ?? '') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('categoria_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="marca" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                            <input type="text" name="marca" id="marca"
                                   value="{{ old('marca', $producto->marca ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('marca') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="precio_compra" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio compra <span class="text-red-500">*</span></label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="precio_compra" id="precio_compra"
                                       value="{{ old('precio_compra', $producto->precio_compra ?? '') }}"
                                       step="0.01" min="0"
                                       class="block w-full pl-7 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('precio_compra') border-red-500 @enderror"
                                       required>
                            </div>
                            @error('precio_compra') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="precio_venta" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio venta <span class="text-red-500">*</span></label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" name="precio_venta" id="precio_venta"
                                       value="{{ old('precio_venta', $producto->precio_venta ?? '') }}"
                                       step="0.01" min="0"
                                       class="block w-full pl-7 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('precio_venta') border-red-500 @enderror"
                                       required>
                            </div>
                            @error('precio_venta') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="stock_minimo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock mínimo <span class="text-red-500">*</span></label>
                            <input type="number" name="stock_minimo" id="stock_minimo"
                                   value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}"
                                   min="0"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('stock_minimo') border-red-500 @enderror"
                                   required>
                            @error('stock_minimo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            @isset($producto)
                                <p class="mt-1 text-xs text-gray-500">Stock actual: <strong>{{ $producto->stock }}</strong> (se modifica vía movimientos de stock)</p>
                            @endisset
                        </div>
                    </div>

                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado <span class="text-red-500">*</span></label>
                        <select name="estado" id="estado"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            <option value="activo"   {{ old('estado', $producto->estado ?? 'activo') === 'activo'   ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado', $producto->estado ?? '')       === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                </div>

                {{-- Columna derecha: imagen --}}
                <div class="space-y-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Imagen del producto</label>

                    <div class="text-center">
                        <img id="preview-imagen"
                             src="{{ isset($producto) && $producto->imagen ? asset('storage/' . $producto->imagen) : '' }}"
                             alt="Preview"
                             class="img-thumbnail mx-auto rounded border border-gray-300 dark:border-gray-600 {{ isset($producto) && $producto->imagen ? '' : 'hidden' }}"
                             style="max-height:200px;object-fit:contain;">
                        <div id="sin-imagen" class="{{ isset($producto) && $producto->imagen ? 'hidden' : '' }} bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center mx-auto"
                             style="height:200px;max-width:200px;">
                            <span class="text-sm text-gray-400">Sin imagen</span>
                        </div>
                    </div>

                    <input type="file" name="imagen" id="imagen"
                           accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300">
                    <p class="text-xs text-gray-500">JPG, PNG o WEBP. Máx. 2 MB.</p>
                    @error('imagen') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="flex items-center justify-end gap-3 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('productos.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ isset($producto) ? 'Guardar cambios' : 'Crear producto' }}
                </button>
            </div>

        </form>

    </div>
</div>

<script>
document.getElementById('imagen').addEventListener('change', function () {
    const file = this.files[0];
    const preview = document.getElementById('preview-imagen');
    const sinImagen = document.getElementById('sin-imagen');

    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            sinImagen.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endsection
