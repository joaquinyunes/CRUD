@extends('layouts.app')

@section('page_title', isset($venta) ? 'Editar venta' : 'Nueva venta')

@section('content')
<div class="py-6">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                {{ isset($venta) ? 'Editar venta' : 'Nueva venta' }}
            </h1>
            <a href="{{ route('ventas.index') }}"
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
              action="{{ isset($venta) ? route('ventas.update', $venta) : route('ventas.store') }}"
              class="space-y-6">

            @csrf
            @isset($venta)
                @method('PUT')
            @endisset

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Datos de la venta</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="cliente_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-red-500">*</span></label>
                        <select name="cliente_id" id="cliente_id"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            <option value="">— Seleccioná un cliente —</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}"
                                    {{ old('cliente_id', $venta->cliente_id ?? '') == $cli->id ? 'selected' : '' }}>
                                    {{ $cli->nombre }} {{ $cli->apellido }}
                                </option>
                            @endforeach
                        </select>
                        @error('cliente_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="fecha" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha" id="fecha"
                               value="{{ old('fecha', isset($venta) ? $venta->fecha->format('Y-m-d') : date('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               required>
                        @error('fecha') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado <span class="text-red-500">*</span></label>
                        <select name="estado" id="estado"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required>
                            <option value="pendiente"  {{ old('estado', $venta->estado ?? 'pendiente') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                            <option value="completada" {{ old('estado', $venta->estado ?? '') === 'completada' ? 'selected' : '' }}>Completada</option>
                            <option value="cancelada"  {{ old('estado', $venta->estado ?? '') === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
                        </select>
                        @error('estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Detalle de productos</h2>
                    <button type="button" id="agregar-detalle"
                            class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-500">
                        + Agregar producto
                    </button>
                </div>

                <div id="detalles-container" class="space-y-3">
                    @if(isset($venta) && $venta->detalles->count())
                        @foreach($venta->detalles as $index => $detalle)
                            <div class="detalle-row flex flex-wrap gap-3 items-end p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Producto</label>
                                    <select name="detalles[{{ $index }}][producto_id]"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
                                            required>
                                        <option value="">— Seleccioná —</option>
                                        @foreach($productos as $prod)
                                            <option value="{{ $prod->id }}"
                                                {{ $detalle->producto_id == $prod->id ? 'selected' : '' }}>
                                                {{ $prod->nombre }} (${{ number_format($prod->precio_venta, 2, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-24">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Cantidad</label>
                                    <input type="number" name="detalles[{{ $index }}][cantidad]"
                                           value="{{ $detalle->cantidad }}" min="1"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm cantidad-input"
                                           required>
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Precio</label>
                                    <input type="number" name="detalles[{{ $index }}][precio]"
                                           value="{{ $detalle->precio }}" step="0.01" min="0"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm precio-input"
                                           required>
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Subtotal</label>
                                    <input type="text" readonly
                                           value="${{ number_format($detalle->subtotal, 2, ',', '.') }}"
                                           class="mt-1 block w-full rounded-md border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 text-sm subtotal-display bg-gray-100">
                                </div>
                                <button type="button" class="quitar-detalle mb-1 px-2 py-1 text-red-500 hover:text-red-700 text-xs">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="flex justify-end mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="text-right">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total:</span>
                        <span id="total-venta" class="ml-2 text-lg font-semibold text-gray-800 dark:text-gray-100">$0.00</span>
                    </div>
                </div>

                @error('detalles') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('ventas.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Cancelar
                </a>
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    {{ isset($venta) ? 'Guardar cambios' : 'Registrar venta' }}
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
            `<option value="${p.id}">${p.nombre} ($${parseFloat(p.precio_venta).toFixed(2)})</option>`
        ).join('');

        const html = `
            <div class="detalle-row flex flex-wrap gap-3 items-end p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">Producto</label>
                    <select name="detalles[${idx}][producto_id]"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm"
                            required>
                        <option value="">— Seleccioná —</option>
                        ${options}
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">Cantidad</label>
                    <input type="number" name="detalles[${idx}][cantidad]"
                           value="1" min="1"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm cantidad-input"
                           required>
                </div>
                <div class="w-32">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">Precio</label>
                    <input type="number" name="detalles[${idx}][precio]"
                           value="0.00" step="0.01" min="0"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm precio-input"
                           required>
                </div>
                <div class="w-32">
                    <label class="block text-xs text-gray-500 dark:text-gray-400">Subtotal</label>
                    <input type="text" readonly value="$0.00"
                           class="mt-1 block w-full rounded-md border-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 text-sm subtotal-display bg-gray-100">
                </div>
                <button type="button" class="quitar-detalle mb-1 px-2 py-1 text-red-500 hover:text-red-700 text-xs">
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
        document.getElementById('total-venta').textContent = '$' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    bindEvents();
    recalcularTotal();
});
</script>
@endsection
