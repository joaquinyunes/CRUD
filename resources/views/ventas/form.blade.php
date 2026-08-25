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

        <p class="r-caption mb-3" style="opacity:.75;">
            Atajos: <kbd>F2</kbd> lector de código · <kbd>Alt</kbd>+<kbd>P</kbd> agregar fila ·
            <kbd>Alt</kbd>+<kbd>M</kbd> medio de pago · <kbd>F9</kbd> guardar
        </p>

        <form method="POST"
              id="venta-form"
              data-buscar-url="{{ route('productos.buscar') }}"
              action="{{ isset($venta) ? route('ventas.update', $venta) : route('ventas.store') }}"
              class="space-y-6">

            @csrf
            @isset($venta)
                @method('PUT')
            @endisset

            <div class="r-card-flat">
                <h2 class="r-label" style="margin-bottom:1rem;">Datos de la venta</h2>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    @if(($depositos ?? collect())->count() > 1)
                    <div>
                        <label for="deposito_id" class="r-label">Depósito</label>
                        <select name="deposito_id" id="deposito_id" class="r-select">
                            @foreach($depositos as $dep)
                                <option value="{{ $dep->id }}" {{ old('deposito_id', $venta->deposito_id ?? '') == $dep->id ? 'selected' : '' }}>{{ $dep->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <label for="cliente_id" class="r-label">Cliente <span class="text-red-500">*</span></label>
                        <select name="cliente_id" id="cliente_id"
                                class="r-select"
                                required>
                            <option value="">— Seleccioná un cliente —</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}"
                                    {{ old('cliente_id', $venta->cliente_id ?? '') == $cli->id ? 'selected' : '' }}>
                                    {{ $cli->nombre }} {{ $cli->apellido }}
                                </option>
                            @endforeach
                        </select>
                        @error('cliente_id') <p class="r-caption" style="color:#dc2626;">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="fecha" class="r-label">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="fecha" id="fecha"
                               value="{{ old('fecha', isset($venta) ? $venta->fecha->format('Y-m-d') : date('Y-m-d')) }}"
                               class="r-input"
                               required>
                        @error('fecha') <p class="r-caption" style="color:#dc2626;">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="estado" class="r-label">Estado <span class="text-red-500">*</span></label>
                        <select name="estado" id="estado"
                                class="r-select"
                                required>
                            <option value="pendiente"  {{ old('estado', $venta->estado ?? 'pendiente') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                            <option value="completada" {{ old('estado', $venta->estado ?? '') === 'completada' ? 'selected' : '' }}>Completada</option>
                            <option value="cancelada"  {{ old('estado', $venta->estado ?? '') === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
                        </select>
                        @error('estado') <p class="r-caption" style="color:#dc2626;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="r-card-flat">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="r-label">Detalle de productos</h2>
                    <button type="button" id="agregar-detalle"
                            class="r-btn r-btn-primary r-btn-sm">
                        + Agregar producto
                    </button>
                </div>

                <div class="mb-4">
                    <label for="scan-codigo" class="r-caption">Escaneá o escribí un código / nombre y presioná Enter</label>
                    <input type="text" id="scan-codigo" autocomplete="off"
                           placeholder="Código de barras, código interno o nombre…"
                           class="r-input">
                    <p id="scan-msg" class="r-caption" style="min-height:1.1em;"></p>
                </div>

                <div id="detalles-container" class="space-y-3">
                    @if(isset($venta) && $venta->detalles->count())
                        @foreach($venta->detalles as $index => $detalle)
                            <div class="detalle-row flex flex-wrap gap-3 items-end p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="r-caption">Producto</label>
                                    <select name="detalles[{{ $index }}][producto_id]"
                                            class="r-select"
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
                                    <label class="r-caption">Cantidad</label>
                                    <input type="number" name="detalles[{{ $index }}][cantidad]"
                                           value="{{ $detalle->cantidad }}" min="1"
                                           class="r-input cantidad-input"
                                           required>
                                </div>
                                <div class="w-32">
                                    <label class="r-caption">Precio</label>
                                    <input type="number" name="detalles[{{ $index }}][precio]"
                                           value="{{ $detalle->precio }}" step="0.01" min="0"
                                           class="r-input precio-input"
                                           required>
                                </div>
                                <div class="w-32">
                                    <label class="r-caption">Subtotal</label>
                                    <input type="text" readonly
                                           value="${{ number_format($detalle->subtotal, 2, ',', '.') }}"
                                           class="r-input subtotal-display" style="background:var(--color-bg-muted); opacity:0.7;">
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
                        <span class="r-caption">Subtotal:</span>
                        <span id="subtotal-venta" class="ml-2 text-lg font-semibold text-gray-800 dark:text-gray-100">$0.00</span>
                    </div>
                </div>

                @error('detalles') <p class="r-caption" style="color:#dc2626;">{{ $message }}</p> @enderror
            </div>

            <div class="r-card-flat">
                <h2 class="r-label" style="margin-bottom:1rem;">Descuento</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="descuento_tipo" class="r-label">Tipo de descuento</label>
                        <select name="descuento_tipo" id="descuento_tipo" class="r-select">
                            <option value="fijo" {{ old('descuento_tipo', $venta->descuento_tipo ?? 'fijo') === 'fijo' ? 'selected' : '' }}>Monto fijo</option>
                            <option value="porcentaje" {{ old('descuento_tipo', $venta->descuento_tipo ?? '') === 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                        </select>
                    </div>
                    <div>
                        <label for="descuento" class="r-label">Monto del descuento</label>
                        <input type="number" name="descuento" id="descuento"
                               value="{{ old('descuento', $venta->descuento ?? 0) }}"
                               step="0.01" min="0"
                               class="r-input">
                    </div>
                </div>
            </div>

            <div class="r-card-flat">
                <h2 class="r-label" style="margin-bottom:1rem;">Medios de pago</h2>

                <div id="pagos-container" class="space-y-3">
                    @if(isset($venta) && $venta->pagos->count())
                        @foreach($venta->pagos as $idx => $pago)
                            <div class="pago-row flex flex-wrap gap-3 items-end p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex-1 min-w-[180px]">
                                    <label class="r-caption">Método de pago</label>
                                    <select name="metodos_pago[{{ $idx }}][metodo_pago_id]"
                                            class="r-select"
                                            required>
                                        <option value="">— Seleccioná —</option>
                                        @foreach($metodosPago as $mp)
                                            <option value="{{ $mp->id }}"
                                                {{ $pago->metodo_pago_id == $mp->id ? 'selected' : '' }}>
                                                {{ $mp->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-32">
                                    <label class="r-caption">Monto</label>
                                    <input type="number" name="metodos_pago[{{ $idx }}][monto]"
                                           value="{{ $pago->monto }}" step="0.01" min="0"
                                           class="r-input pago-monto"
                                           required>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="r-caption">Referencia</label>
                                    <input type="text" name="metodos_pago[{{ $idx }}][referencia]"
                                           value="{{ $pago->referencia }}"
                                           class="r-input"
                                           placeholder="Opcional">
                                </div>
                                <button type="button" class="quitar-pago mb-1 px-2 py-1 text-red-500 hover:text-red-700 text-xs">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="mt-3">
                    <button type="button" id="agregar-pago"
                            class="r-btn r-btn-ghost r-btn-sm">
                        + Agregar medio de pago
                    </button>
                </div>
            </div>

            <div class="r-card-flat">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="r-label">Subtotal:</span>
                        <span id="resumen-subtotal" class="font-semibold">$0.00</span>
                    </div>
                    <div>
                        <span class="r-label">Descuento:</span>
                        <span id="resumen-descuento" class="font-semibold">-$0.00</span>
                    </div>
                    <div>
                        <span class="r-label">Impuesto:</span>
                        <span id="resumen-impuesto" class="font-semibold">$0.00</span>
                    </div>
                    <div>
                        <span class="r-label">Total final:</span>
                        <span id="resumen-total" class="text-lg font-bold text-gray-800 dark:text-gray-100">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('ventas.index') }}"
                   class="r-btn r-btn-ghost">
                    Cancelar
                </a>
                <button type="submit"
                        class="r-btn r-btn-primary">
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

    const pagosContainer = document.getElementById('pagos-container');
    const btnAgregarPago = document.getElementById('agregar-pago');
    let indicePago = pagosContainer.querySelectorAll('.pago-row').length;

    const productos = @json($productos);
    const metodosPago = @json($metodosPago);

    btnAgregar.addEventListener('click', function () {
        agregarFila(indice);
        indice++;
    });

    // ---- Lector de código de barras / búsqueda rápida ----
    const scanInput = document.getElementById('scan-codigo');
    const scanMsg = document.getElementById('scan-msg');
    const buscarUrl = document.getElementById('venta-form').dataset.buscarUrl;

    function setMsg(txt, err = false) {
        scanMsg.textContent = txt || '';
        scanMsg.style.color = err ? '#dc2626' : 'var(--color-text-muted, #6b7280)';
    }

    function filaDeProducto(id) {
        return [...container.querySelectorAll('.detalle-row')].find(row => {
            const sel = row.querySelector('select[name*="[producto_id]"]');
            return sel && sel.value == id;
        });
    }

    function agregarOIncrementar(prod) {
        const existente = filaDeProducto(prod.id);
        if (existente) {
            const cant = existente.querySelector('.cantidad-input');
            cant.value = (parseInt(cant.value) || 0) + 1;
            cant.dispatchEvent(new Event('input'));
            existente.scrollIntoView({ block: 'center', behavior: 'smooth' });
            setMsg(`+1 ${prod.nombre} (cantidad ${cant.value})`);
            return;
        }
        agregarFila(indice, { producto_id: prod.id, precio: prod.precio_venta });
        indice++;
        setMsg(`Agregado: ${prod.nombre}`);
    }

    async function buscarCodigo(q) {
        if (!q) return;
        setMsg('Buscando…');
        try {
            const resp = await fetch(`${buscarUrl}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!resp.ok) { setMsg('No se pudo buscar el producto.', true); return; }
            const data = await resp.json();
            if (!data.length) { setMsg(`Sin resultados para «${q}».`, true); return; }
            const exacto = data.find(p => p.codigo === q || p.codigo_barra === q);
            if (exacto || data.length === 1) {
                agregarOIncrementar(exacto || data[0]);
                scanInput.value = '';
            } else {
                setMsg(`${data.length} coincidencias: ${data.slice(0, 5).map(p => p.nombre).join(', ')}… Precisá el código.`);
            }
        } catch (e) {
            setMsg('Error de red al buscar.', true);
        }
    }

    scanInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarCodigo(this.value.trim());
        }
    });

    // ---- Atajos de teclado ----
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); scanInput.focus(); scanInput.select(); }
        else if (e.key === 'F9') { e.preventDefault(); document.getElementById('venta-form').requestSubmit(); }
        else if (e.altKey && (e.key === 'p' || e.key === 'P')) { e.preventDefault(); btnAgregar.click(); }
        else if (e.altKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); btnAgregarPago.click(); }
    });

    btnAgregarPago.addEventListener('click', function () {
        agregarFilaPago(indicePago);
        indicePago++;
    });

    function agregarFila(idx, preset) {
        const options = productos.map(p =>
            `<option value="${p.id}">${p.nombre} ($${parseFloat(p.precio_venta).toFixed(2)})</option>`
        ).join('');

        const html = `
            <div class="detalle-row flex flex-wrap gap-3 items-end p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="flex-1 min-w-[200px]">
                    <label class="r-caption">Producto</label>
                    <select name="detalles[${idx}][producto_id]"
                            class="r-select"
                            required>
                        <option value="">— Seleccioná —</option>
                        ${options}
                    </select>
                </div>
                <div class="w-24">
                    <label class="r-caption">Cantidad</label>
                    <input type="number" name="detalles[${idx}][cantidad]"
                           value="1" min="1"
                           class="r-input cantidad-input"
                           required>
                </div>
                <div class="w-32">
                    <label class="r-caption">Precio</label>
                    <input type="number" name="detalles[${idx}][precio]"
                           value="0.00" step="0.01" min="0"
                           class="r-input precio-input"
                           required>
                </div>
                <div class="w-32">
                    <label class="r-caption">Subtotal</label>
                    <input type="text" readonly value="$0.00"
                           class="r-input subtotal-display" style="background:var(--color-bg-muted); opacity:0.7;">
                </div>
                <button type="button" class="quitar-detalle mb-1 px-2 py-1 text-red-500 hover:text-red-700 text-xs">
                    ✕
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        const row = container.lastElementChild;
        bindDetalleEvents();

        if (preset) {
            const sel = row.querySelector('select[name*="[producto_id]"]');
            if (preset.producto_id) sel.value = preset.producto_id;
            if (preset.precio != null) row.querySelector('.precio-input').value = parseFloat(preset.precio).toFixed(2);
            row.querySelector('.cantidad-input').dispatchEvent(new Event('input'));
        }
        return row;
    }

    function agregarFilaPago(idx) {
        const options = metodosPago.map(mp =>
            `<option value="${mp.id}">${mp.nombre}</option>`
        ).join('');

        const html = `
            <div class="pago-row flex flex-wrap gap-3 items-end p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <div class="flex-1 min-w-[180px]">
                    <label class="r-caption">Método de pago</label>
                    <select name="metodos_pago[${idx}][metodo_pago_id]"
                            class="r-select"
                            required>
                        <option value="">— Seleccioná —</option>
                        ${options}
                    </select>
                </div>
                <div class="w-32">
                    <label class="r-caption">Monto</label>
                    <input type="number" name="metodos_pago[${idx}][monto]"
                           value="0.00" step="0.01" min="0"
                           class="r-input pago-monto"
                           required>
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="r-caption">Referencia</label>
                    <input type="text" name="metodos_pago[${idx}][referencia]"
                           class="r-input"
                           placeholder="Opcional">
                </div>
                <button type="button" class="quitar-pago mb-1 px-2 py-1 text-red-500 hover:text-red-700 text-xs">
                    ✕
                </button>
            </div>
        `;

        pagosContainer.insertAdjacentHTML('beforeend', html);
        bindPagoEvents();
    }

    function bindDetalleEvents() {
        container.querySelectorAll('.quitar-detalle').forEach(btn => {
            btn.onclick = function () {
                this.closest('.detalle-row').remove();
                recalcularTotales();
            };
        });

        container.querySelectorAll('select[name*="[producto_id]"]').forEach(sel => {
            sel.onchange = function () {
                const prod = productos.find(p => p.id == this.value);
                if (prod) {
                    const precioInput = this.closest('.detalle-row').querySelector('.precio-input');
                    if (!parseFloat(precioInput.value)) {
                        precioInput.value = parseFloat(prod.precio_venta).toFixed(2);
                    }
                    this.closest('.detalle-row').querySelector('.cantidad-input').dispatchEvent(new Event('input'));
                }
            };
        });

        container.querySelectorAll('.cantidad-input, .precio-input').forEach(input => {
            input.oninput = function () {
                const row = this.closest('.detalle-row');
                const cantidad = parseFloat(row.querySelector('.cantidad-input').value) || 0;
                const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
                const subtotal = cantidad * precio;
                row.querySelector('.subtotal-display').value = '$' + subtotal.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                recalcularTotales();
            };
        });
    }

    function bindPagoEvents() {
        pagosContainer.querySelectorAll('.quitar-pago').forEach(btn => {
            btn.onclick = function () {
                this.closest('.pago-row').remove();
            };
        });
    }

    function recalcularTotales() {
        let subtotal = 0;
        container.querySelectorAll('.detalle-row').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad-input')?.value) || 0;
            const precio = parseFloat(row.querySelector('.precio-input')?.value) || 0;
            subtotal += cantidad * precio;
        });

        const descuentoTipo = document.getElementById('descuento_tipo').value;
        const descuentoValor = parseFloat(document.getElementById('descuento').value) || 0;
        const descuento = descuentoTipo === 'porcentaje' ? (subtotal * descuentoValor / 100) : descuentoValor;

        const ivaPorcentaje = {{ \App\Models\Setting::obtener('sistema_iva', '21') }};
        const ivaHabilitado = {{ \App\Models\Setting::obtener('sistema_impuesto_habilitado', '1') === '1' ? 'true' : 'false' }};
        const baseImponible = subtotal - descuento;
        const impuesto = ivaHabilitado ? (baseImponible * ivaPorcentaje / 100) : 0;
        const totalFinal = baseImponible + impuesto;

        const fmt = (v) => '$' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        document.getElementById('subtotal-venta').textContent = fmt(subtotal);
        document.getElementById('resumen-subtotal').textContent = fmt(subtotal);
        document.getElementById('resumen-descuento').textContent = '-' + fmt(descuento);
        document.getElementById('resumen-impuesto').textContent = fmt(impuesto);
        document.getElementById('resumen-total').textContent = fmt(totalFinal);
    }

    document.getElementById('descuento_tipo').addEventListener('change', recalcularTotales);
    document.getElementById('descuento').addEventListener('input', recalcularTotales);

    bindDetalleEvents();
    bindPagoEvents();
    recalcularTotales();
});
</script>
@endsection
