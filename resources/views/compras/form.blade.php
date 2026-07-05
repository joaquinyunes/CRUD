@extends('layouts.app')

@section('page_title', isset($compra) ? 'Editar compra' : 'Nueva compra')

@section('content')
<div class="r-mb-8" data-reveal="fade-up">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

        <div class="r-flex r-items-center r-justify-between r-mb-6">
            <h1 class="r-display-m">
                {{ isset($compra) ? 'Editar compra' : 'Nueva compra' }}
            </h1>
            <a href="{{ route('compras.index') }}" class="r-btn r-btn-ghost r-caption">
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
              action="{{ isset($compra) ? route('compras.update', $compra) : route('compras.store') }}"
              class="r-gap-6">

            @csrf
            @isset($compra)
                @method('PUT')
            @endisset

            <div class="r-card-flat r-mb-6" data-reveal="fade-up">
                <h2 class="r-label r-mb-4">Datos de la compra</h2>

                <div class="r-flex r-gap-4" style="flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label for="proveedor_id" class="r-label">Proveedor <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                        <select name="proveedor_id" id="proveedor_id"
                                class="r-select"
                                required>
                            <option value="">— Seleccioná un proveedor —</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}"
                                    {{ old('proveedor_id', $compra->proveedor_id ?? '') == $prov->id ? 'selected' : '' }}>
                                    {{ $prov->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('proveedor_id') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>

                    <div style="flex: 1; min-width: 200px;">
                        <label for="fecha" class="r-label">Fecha <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                        <input type="date" name="fecha" id="fecha"
                               value="{{ old('fecha', isset($compra) ? $compra->fecha->format('Y-m-d') : date('Y-m-d')) }}"
                               class="r-input"
                               required>
                        @error('fecha') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>

                    <div style="flex: 1; min-width: 200px;">
                        <label for="estado" class="r-label">Estado <span class="r-tag" style="background: var(--r-color-danger); color: #fff;">*</span></label>
                        <select name="estado" id="estado"
                                class="r-select"
                                required>
                            <option value="pendiente"  {{ old('estado', $compra->estado ?? 'pendiente') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                            <option value="completada" {{ old('estado', $compra->estado ?? '') === 'completada' ? 'selected' : '' }}>Completada</option>
                            <option value="cancelada"  {{ old('estado', $compra->estado ?? '') === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
                        </select>
                        @error('estado') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="r-card-flat r-mb-6" data-reveal="fade-up">
                <div class="r-flex r-items-center r-justify-between r-mb-4">
                    <h2 class="r-label">Detalle de productos</h2>
                    <button type="button" id="agregar-detalle"
                            class="r-btn r-btn-accent">
                        + Agregar producto
                    </button>
                </div>

                <div id="detalles-container" class="r-gap-3">
                    @if(isset($compra) && $compra->detalles->count())
                        @foreach($compra->detalles as $index => $detalle)
                            <div class="detalle-row r-flex r-items-center r-gap-3 r-card-flat">
                                <div style="flex: 1; min-width: 200px;">
                                    <label class="r-caption">Producto</label>
                                    <select name="detalles[{{ $index }}][producto_id]"
                                            class="r-select"
                                            required>
                                        <option value="">— Seleccioná —</option>
                                        @foreach($productos as $prod)
                                            <option value="{{ $prod->id }}"
                                                {{ $detalle->producto_id == $prod->id ? 'selected' : '' }}>
                                                {{ $prod->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width: 100px;">
                                    <label class="r-caption">Cantidad</label>
                                    <input type="number" name="detalles[{{ $index }}][cantidad]"
                                           value="{{ $detalle->cantidad }}" min="1"
                                           class="r-input cantidad-input"
                                           required>
                                </div>
                                <div style="width: 130px;">
                                    <label class="r-caption">Precio</label>
                                    <input type="number" name="detalles[{{ $index }}][precio]"
                                           value="{{ $detalle->precio }}" step="0.01" min="0"
                                           class="r-input precio-input"
                                           required>
                                </div>
                                <div style="width: 130px;">
                                    <label class="r-caption">Subtotal</label>
                                    <input type="text" readonly
                                           value="${{ number_format($detalle->subtotal, 2, ',', '.') }}"
                                           class="r-input subtotal-display" style="background: var(--r-color-bg-alt);">
                                </div>
                                <button type="button" class="quitar-detalle r-btn r-btn-ghost" style="color: var(--r-color-danger);">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="r-flex r-justify-between r-mt-4" style="border-top: 1px solid var(--r-color-border); padding-top: 1rem;">
                    <span class="r-caption">Total:</span>
                    <span id="total-compra" class="r-display-m">$0.00</span>
                </div>

                @error('detalles') <p class="r-caption r-mt-2" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div class="r-flex r-items-center r-justify-between">
                <a href="{{ route('compras.index') }}" class="r-btn r-btn-ghost r-caption">
                    Cancelar
                </a>
                <button type="submit" class="r-btn r-btn-primary">
                    {{ isset($compra) ? 'Guardar cambios' : 'Registrar compra' }}
                </button>
            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('detalles-container');
    const btnAgregar = document.getElementById('agregar-detalle');
    let indice = container.querySelectorAll('.detalle-row').length;

    const productos = @json($productos);

    btnAgregar.addEventListener('click', function () {
        agregarFila(indice);
        indice++;
    });

    function agregarFila(idx) {
        const options = productos.map(p =>
            `<option value="${p.id}">${p.nombre}</option>`
        ).join('');

        const html = `
            <div class="detalle-row r-flex r-items-center r-gap-3 r-card-flat">
                <div style="flex: 1; min-width: 200px;">
                    <label class="r-caption">Producto</label>
                    <select name="detalles[${idx}][producto_id]"
                            class="r-select"
                            required>
                        <option value="">— Seleccioná —</option>
                        ${options}
                    </select>
                </div>
                <div style="width: 100px;">
                    <label class="r-caption">Cantidad</label>
                    <input type="number" name="detalles[${idx}][cantidad]"
                           value="1" min="1"
                           class="r-input cantidad-input"
                           required>
                </div>
                <div style="width: 130px;">
                    <label class="r-caption">Precio</label>
                    <input type="number" name="detalles[${idx}][precio]"
                           value="0.00" step="0.01" min="0"
                           class="r-input precio-input"
                           required>
                </div>
                <div style="width: 130px;">
                    <label class="r-caption">Subtotal</label>
                    <input type="text" readonly value="$0.00"
                           class="r-input subtotal-display" style="background: var(--r-color-bg-alt);">
                </div>
                <button type="button" class="quitar-detalle r-btn r-btn-ghost" style="color: var(--r-color-danger);">
                    ✕
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        bindEvents();
    }

    function bindEvents() {
        container.querySelectorAll('.quitar-detalle').forEach(btn => {
            btn.onclick = function () {
                this.closest('.detalle-row').remove();
                recalcularTotal();
            };
        });

        container.querySelectorAll('.cantidad-input, .precio-input').forEach(input => {
            input.oninput = function () {
                const row = this.closest('.detalle-row');
                const cantidad = parseFloat(row.querySelector('.cantidad-input').value) || 0;
                const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
                const subtotal = cantidad * precio;
                row.querySelector('.subtotal-display').value = '$' + subtotal.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                recalcularTotal();
            };
        });
    }

    function recalcularTotal() {
        let total = 0;
        container.querySelectorAll('.detalle-row').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad-input')?.value) || 0;
            const precio = parseFloat(row.querySelector('.precio-input')?.value) || 0;
            total += cantidad * precio;
        });
        document.getElementById('total-compra').textContent = '$' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    bindEvents();
    recalcularTotal();
});
</script>
@endsection
