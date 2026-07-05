@extends('layouts.app')

@section('page_title', isset($producto) ? 'Editar producto' : 'Nuevo producto')

@section('content')
<div class="r-mb-8" data-reveal="fade-up">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

        <div class="r-flex r-items-center r-justify-between r-mb-6">
            <h1 class="r-display-m">
                {{ isset($producto) ? 'Editar producto' : 'Nuevo producto' }}
            </h1>
            <a href="{{ route('productos.index') }}" class="r-btn r-btn-ghost r-caption">
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
              action="{{ isset($producto) ? route('productos.update', $producto) : route('productos.store') }}"
              enctype="multipart/form-data"
              class="r-card-flat r-mb-6">
            @csrf
            @isset($producto)
                @method('PUT')
            @endisset

            <div class="r-flex r-gap-6" style="flex-wrap: wrap;">

                {{-- Columna izquierda: campos principales --}}
                <div style="flex: 2; min-width: 300px;" class="r-gap-4" data-reveal="fade-up">

                    <div class="r-flex r-gap-4" style="flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 180px;">
                            <label for="codigo" class="r-label">Código <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                            <input type="text" name="codigo" id="codigo"
                                   value="{{ old('codigo', $producto->codigo ?? '') }}"
                                   class="r-input @error('codigo') is-invalid @enderror"
                                   required>
                            @error('codigo') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                        </div>

                        <div style="flex: 2; min-width: 220px;">
                            <label for="nombre" class="r-label">Nombre <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                            <input type="text" name="nombre" id="nombre"
                                   value="{{ old('nombre', $producto->nombre ?? '') }}"
                                   class="r-input @error('nombre') is-invalid @enderror"
                                   required>
                            @error('nombre') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="descripcion" class="r-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="3"
                                  class="r-input">{{ old('descripcion', $producto->descripcion ?? '') }}</textarea>
                        @error('descripcion') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>

                    <div class="r-flex r-gap-4" style="flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label for="categoria_id" class="r-label">Categoría <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                            <select name="categoria_id" id="categoria_id"
                                    class="r-select @error('categoria_id') is-invalid @enderror"
                                    required>
                                <option value="">— Seleccioná una categoría —</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('categoria_id', $producto->categoria_id ?? '') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('categoria_id') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                        </div>

                        <div style="flex: 1; min-width: 200px;">
                            <label for="marca" class="r-label">Marca</label>
                            <input type="text" name="marca" id="marca"
                                   value="{{ old('marca', $producto->marca ?? '') }}"
                                   class="r-input">
                            @error('marca') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="r-flex r-gap-4" style="flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 160px;">
                            <label for="precio_compra" class="r-label">Precio compra <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                            <div class="r-flex r-items-center r-gap-2">
                                <span class="r-caption">$</span>
                                <input type="number" name="precio_compra" id="precio_compra"
                                       value="{{ old('precio_compra', $producto->precio_compra ?? '') }}"
                                       step="0.01" min="0"
                                       class="r-input @error('precio_compra') is-invalid @enderror"
                                       required style="flex:1;">
                            </div>
                            @error('precio_compra') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                        </div>

                        <div style="flex: 1; min-width: 160px;">
                            <label for="precio_venta" class="r-label">Precio venta <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                            <div class="r-flex r-items-center r-gap-2">
                                <span class="r-caption">$</span>
                                <input type="number" name="precio_venta" id="precio_venta"
                                       value="{{ old('precio_venta', $producto->precio_venta ?? '') }}"
                                       step="0.01" min="0"
                                       class="r-input @error('precio_venta') is-invalid @enderror"
                                       required style="flex:1;">
                            </div>
                            @error('precio_venta') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                        </div>

                        <div style="flex: 1; min-width: 160px;">
                            <label for="stock_minimo" class="r-label">Stock mínimo <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                            <input type="number" name="stock_minimo" id="stock_minimo"
                                   value="{{ old('stock_minimo', $producto->stock_minimo ?? 0) }}"
                                   min="0"
                                   class="r-input @error('stock_minimo') is-invalid @enderror"
                                   required>
                            @error('stock_minimo') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                            @isset($producto)
                                <p class="r-caption">Stock actual: <strong>{{ $producto->stock }}</strong> (se modifica vía movimientos de stock)</p>
                            @endisset
                        </div>
                    </div>

                    <div>
                        <label for="unidad_medida_id" class="r-label">Unidad de Medida</label>
                        <select name="unidad_medida_id" id="unidad_medida_id" class="r-select">
                            <option value="">— Sin unidad —</option>
                            @foreach($unidadesMedida as $um)
                                <option value="{{ $um->id }}"
                                    {{ old('unidad_medida_id', $producto->unidad_medida_id ?? '') == $um->id ? 'selected' : '' }}>
                                    {{ $um->nombre }} ({{ $um->abreviacion }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="estado" class="r-label">Estado <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                        <select name="estado" id="estado" class="r-select" required>
                            <option value="activo"   {{ old('estado', $producto->estado ?? 'activo') === 'activo'   ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado', $producto->estado ?? '')       === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                </div>

                {{-- Columna derecha: imagen --}}
                <div style="flex: 1; min-width: 240px;" class="r-gap-4" data-reveal="fade-up">
                    <label class="r-label">Imagen del producto</label>

                    <div class="r-text-right">
                        <img id="preview-imagen"
                             src="{{ isset($producto) && $producto->imagen ? asset('storage/' . $producto->imagen) : '' }}"
                             alt="Preview"
                             class="mx-auto rounded {{ isset($producto) && $producto->imagen ? '' : 'hidden' }}"
                             style="max-height:200px; object-fit:contain; border: 1px solid var(--r-color-border);">
                        <div id="sin-imagen" class="{{ isset($producto) && $producto->imagen ? 'hidden' : '' }} r-flex r-items-center r-justify-between mx-auto"
                             style="height:200px; max-width:200px; background: var(--r-color-bg-alt); border-radius: var(--r-radius);">
                            <span class="r-caption">Sin imagen</span>
                        </div>
                    </div>

                    <input type="file" name="imagen" id="imagen"
                           accept="image/jpeg,image/png,image/webp"
                           class="r-input">
                    <p class="r-caption">JPG, PNG o WEBP. Máx. 2 MB.</p>
                    @error('imagen') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                </div>

            </div>

            <div class="r-flex r-items-center r-justify-between r-mt-6" style="border-top: 1px solid var(--r-color-border); padding-top: 1.5rem;">
                <a href="{{ route('productos.index') }}" class="r-btn r-btn-ghost r-caption">
                    Cancelar
                </a>
                <button type="submit" class="r-btn r-btn-primary">
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
