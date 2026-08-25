@extends('layouts.app')

@section('page_title', isset($compra) ? 'Editar compra' : 'Nueva compra')

@section('content')
<div class="r-mb-8" data-reveal="fade-up">
    <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

        <div class="r-flex r-items-center r-justify-between r-mb-6">
            <h1 class="r-display-m">{{ isset($compra) ? 'Editar compra' : 'Nueva compra' }}</h1>
            <a href="{{ route('compras.index') }}" class="r-btn r-btn-ghost r-caption">&larr; Volver</a>
        </div>

        @if($errors->any())
            <div class="r-card-flat r-mb-4 r-body" style="border-left: 3px solid var(--r-color-danger);">
                <ul class="list-disc list-inside r-gap-2">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <p class="r-caption r-mb-3" style="opacity:.75;">
            Atajos: <kbd>F2</kbd> lector de código · <kbd>Alt</kbd>+<kbd>P</kbd> agregar fila ·
            <kbd>Alt</kbd>+<kbd>M</kbd> medio de pago · <kbd>F9</kbd> guardar
        </p>

        <form method="POST"
              id="compra-form"
              data-buscar-url="{{ route('productos.buscar') }}"
              action="{{ isset($compra) ? route('compras.update', $compra) : route('compras.store') }}"
              class="r-gap-6">

            @csrf
            @isset($compra)@method('PUT')@endisset

            <div class="r-card-flat r-mb-6">
                <h2 class="r-label r-mb-4">Datos de la compra</h2>
                <div class="r-flex r-gap-4" style="flex-wrap: wrap;">
                    @if(($depositos ?? collect())->count() > 1)
                    <div style="flex: 1; min-width: 200px;">
                        <label for="deposito_id" class="r-label">Depósito</label>
                        <select name="deposito_id" id="deposito_id" class="r-select">
                            @foreach($depositos as $dep)
                                <option value="{{ $dep->id }}" {{ old('deposito_id', $compra->deposito_id ?? '') == $dep->id ? 'selected' : '' }}>{{ $dep->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div style="flex: 1; min-width: 200px;">
                        <label for="proveedor_id" class="r-label">Proveedor <span style="color:var(--r-color-danger);">*</span></label>
                        <select name="proveedor_id" id="proveedor_id" class="r-select" required>
                            <option value="">— Seleccioná un proveedor —</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}" {{ old('proveedor_id', $compra->proveedor_id ?? '') == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                            @endforeach
                        </select>
                        @error('proveedor_id') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label for="fecha" class="r-label">Fecha <span style="color:var(--r-color-danger);">*</span></label>
                        <input type="date" name="fecha" id="fecha"
                               value="{{ old('fecha', isset($compra) ? $compra->fecha->format('Y-m-d') : date('Y-m-d')) }}"
                               class="r-input" required>
                        @error('fecha') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label for="estado" class="r-label">Estado <span style="color:var(--r-color-danger);">*</span></label>
                        <select name="estado" id="estado" class="r-select" required>
                            <option value="pendiente"  {{ old('estado', $compra->estado ?? 'pendiente') === 'pendiente'  ? 'selected' : '' }}>Pendiente</option>
                            <option value="completada" {{ old('estado', $compra->estado ?? '') === 'completada' ? 'selected' : '' }}>Completada</option>
                            <option value="cancelada"  {{ old('estado', $compra->estado ?? '') === 'cancelada'  ? 'selected' : '' }}>Cancelada</option>
                        </select>
                        @error('estado') <p class="r-caption" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="r-card-flat r-mb-6">
                <div class="r-flex r-items-center r-justify-between r-mb-4">
                    <h2 class="r-label">Detalle de productos</h2>
                    <button type="button" id="agregar-detalle" class="r-btn r-btn-accent">+ Agregar producto</button>
                </div>

                <div class="r-mb-4">
                    <label for="scan-codigo" class="r-caption">Escaneá o escribí un código / nombre y presioná Enter</label>
                    <input type="text" id="scan-codigo" autocomplete="off"
                           placeholder="Código de barras, código interno o nombre…" class="r-input">
                    <p id="scan-msg" class="r-caption" style="min-height:1.1em;"></p>
                </div>

                <div id="detalles-container" class="r-gap-3">
                    @if(isset($compra) && $compra->detalles->count())
                        @foreach($compra->detalles as $index => $detalle)
                            <div class="detalle-row r-flex r-items-center r-gap-3 r-card-flat">
                                <div style="flex: 1; min-width: 200px;">
                                    <label class="r-caption">Producto</label>
                                    <select name="detalles[{{ $index }}][producto_id]" class="r-select" required>
                                        <option value="">— Seleccioná —</option>
                                        @foreach($productos as $prod)
                                            <option value="{{ $prod->id }}" {{ $detalle->producto_id == $prod->id ? 'selected' : '' }}>{{ $prod->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width: 100px;">
                                    <label class="r-caption">Cantidad</label>
                                    <input type="number" name="detalles[{{ $index }}][cantidad]" value="{{ $detalle->cantidad }}" min="1" class="r-input cantidad-input" required>
                                </div>
                                <div style="width: 130px;">
                                    <label class="r-caption">Precio</label>
                                    <input type="number" name="detalles[{{ $index }}][precio]" value="{{ $detalle->precio }}" step="0.01" min="0" class="r-input precio-input" required>
                                </div>
                                <div style="width: 130px;">
                                    <label class="r-caption">Subtotal</label>
                                    <input type="text" readonly value="${{ number_format($detalle->subtotal, 2, ',', '.') }}" class="r-input subtotal-display" style="background: var(--r-color-bg-alt);">
                                </div>
                                <button type="button" class="quitar-detalle r-btn r-btn-ghost" style="color: var(--r-color-danger);">✕</button>
                            </div>
                        @endforeach
                    @endif
                </div>
                @error('detalles') <p class="r-caption r-mt-2" style="color: var(--r-color-danger);">{{ $message }}</p> @enderror
            </div>

            <div class="r-card-flat r-mb-6">
                <h2 class="r-label r-mb-4">Descuento</h2>
                <div class="r-flex r-gap-4" style="flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <label for="descuento_tipo" class="r-label">Tipo</label>
                        <select name="descuento_tipo" id="descuento_tipo" class="r-select">
                            <option value="fijo" {{ old('descuento_tipo', $compra->descuento_tipo ?? 'fijo') === 'fijo' ? 'selected' : '' }}>Monto fijo</option>
                            <option value="porcentaje" {{ old('descuento_tipo', $compra->descuento_tipo ?? '') === 'porcentaje' ? 'selected' : '' }}>Porcentaje (%)</option>
                        </select>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label for="descuento" class="r-label">Monto del descuento</label>
                        <input type="number" name="descuento" id="descuento" value="{{ old('descuento', $compra->descuento ?? 0) }}" step="0.01" min="0" class="r-input">
                    </div>
                </div>
            </div>

            <div class="r-card-flat r-mb-6">
                <div class="r-flex r-items-center r-justify-between r-mb-4">
                    <h2 class="r-label">Medios de pago</h2>
                    <button type="button" id="agregar-pago" class="r-btn r-btn-ghost r-btn-sm">+ Agregar medio de pago</button>
                </div>
                <div id="pagos-container" class="r-gap-3">
                    @if(isset($compra) && $compra->pagos->count())
                        @foreach($compra->pagos as $idx => $pago)
                            <div class="pago-row r-flex r-items-center r-gap-3 r-card-flat">
                                <div style="flex:1; min-width:180px;">
                                    <label class="r-caption">Método</label>
                                    <select name="metodos_pago[{{ $idx }}][metodo_pago_id]" class="r-select" required>
                                        <option value="">— Seleccioná —</option>
                                        @foreach($metodosPago as $mp)
                                            <option value="{{ $mp->id }}" {{ $pago->metodo_pago_id == $mp->id ? 'selected' : '' }}>{{ $mp->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="width:130px;">
                                    <label class="r-caption">Monto</label>
                                    <input type="number" name="metodos_pago[{{ $idx }}][monto]" value="{{ $pago->monto }}" step="0.01" min="0" class="r-input pago-monto" required>
                                </div>
                                <div style="flex:1; min-width:150px;">
                                    <label class="r-caption">Referencia</label>
                                    <input type="text" name="metodos_pago[{{ $idx }}][referencia]" value="{{ $pago->referencia }}" class="r-input" placeholder="Opcional">
                                </div>
                                <button type="button" class="quitar-pago r-btn r-btn-ghost" style="color: var(--r-color-danger);">✕</button>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="r-card-flat r-mb-6">
                <div class="r-flex r-justify-between" style="flex-wrap:wrap; gap:1rem;">
                    <div><span class="r-caption">Subtotal:</span> <span id="resumen-subtotal" class="r-label">$0.00</span></div>
                    <div><span class="r-caption">Descuento:</span> <span id="resumen-descuento" class="r-label">-$0.00</span></div>
                    <div><span class="r-caption">Impuesto:</span> <span id="resumen-impuesto" class="r-label">$0.00</span></div>
                    <div><span class="r-caption">Total:</span> <span id="total-compra" class="r-display-m">$0.00</span></div>
                    <div><span class="r-caption">A pagar / pagado:</span> <span id="resumen-pagado" class="r-label">$0.00</span></div>
                </div>
            </div>

            <div class="r-flex r-items-center r-justify-between">
                <a href="{{ route('compras.index') }}" class="r-btn r-btn-ghost r-caption">Cancelar</a>
                <button type="submit" class="r-btn r-btn-primary">{{ isset($compra) ? 'Guardar cambios' : 'Registrar compra' }}</button>
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
    const ivaPorcentaje = {{ \App\Models\Setting::obtener('sistema_iva', '21') }};
    const ivaHabilitado = {{ \App\Models\Setting::obtener('sistema_impuesto_habilitado', '1') === '1' ? 'true' : 'false' }};

    const fmt = (v) => '$' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    btnAgregar.addEventListener('click', () => { agregarFila(indice); indice++; });
    btnAgregarPago.addEventListener('click', () => { agregarFilaPago(indicePago); indicePago++; });

    // ---- Lector de código ----
    const scanInput = document.getElementById('scan-codigo');
    const scanMsg = document.getElementById('scan-msg');
    const buscarUrl = document.getElementById('compra-form').dataset.buscarUrl;
    function setMsg(t, err) { scanMsg.textContent = t || ''; scanMsg.style.color = err ? 'var(--r-color-danger)' : 'inherit'; }

    function filaDeProducto(id) {
        return [...container.querySelectorAll('.detalle-row')].find(row => {
            const sel = row.querySelector('select[name*="[producto_id]"]');
            return sel && sel.value == id;
        });
    }
    function agregarOIncrementar(prod) {
        const ex = filaDeProducto(prod.id);
        if (ex) {
            const c = ex.querySelector('.cantidad-input');
            c.value = (parseInt(c.value) || 0) + 1;
            c.dispatchEvent(new Event('input'));
            setMsg(`+1 ${prod.nombre} (cantidad ${c.value})`);
            return;
        }
        agregarFila(indice, { producto_id: prod.id, precio: prod.precio_compra });
        indice++;
        setMsg(`Agregado: ${prod.nombre}`);
    }
    async function buscarCodigo(q) {
        if (!q) return;
        setMsg('Buscando…');
        try {
            const resp = await fetch(`${buscarUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!resp.ok) { setMsg('No se pudo buscar.', true); return; }
            const data = await resp.json();
            if (!data.length) { setMsg(`Sin resultados para «${q}».`, true); return; }
            const exacto = data.find(p => p.codigo === q || p.codigo_barra === q);
            if (exacto || data.length === 1) { agregarOIncrementar(exacto || data[0]); scanInput.value = ''; }
            else { setMsg(`${data.length} coincidencias. Precisá el código.`); }
        } catch (e) { setMsg('Error de red.', true); }
    }
    scanInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); buscarCodigo(scanInput.value.trim()); } });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') { e.preventDefault(); scanInput.focus(); scanInput.select(); }
        else if (e.key === 'F9') { e.preventDefault(); document.getElementById('compra-form').requestSubmit(); }
        else if (e.altKey && (e.key === 'p' || e.key === 'P')) { e.preventDefault(); btnAgregar.click(); }
        else if (e.altKey && (e.key === 'm' || e.key === 'M')) { e.preventDefault(); btnAgregarPago.click(); }
    });

    function agregarFila(idx, preset) {
        const options = productos.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
        const html = `
            <div class="detalle-row r-flex r-items-center r-gap-3 r-card-flat">
                <div style="flex: 1; min-width: 200px;"><label class="r-caption">Producto</label>
                    <select name="detalles[${idx}][producto_id]" class="r-select" required><option value="">— Seleccioná —</option>${options}</select></div>
                <div style="width: 100px;"><label class="r-caption">Cantidad</label>
                    <input type="number" name="detalles[${idx}][cantidad]" value="1" min="1" class="r-input cantidad-input" required></div>
                <div style="width: 130px;"><label class="r-caption">Precio</label>
                    <input type="number" name="detalles[${idx}][precio]" value="0.00" step="0.01" min="0" class="r-input precio-input" required></div>
                <div style="width: 130px;"><label class="r-caption">Subtotal</label>
                    <input type="text" readonly value="$0.00" class="r-input subtotal-display" style="background: var(--r-color-bg-alt);"></div>
                <button type="button" class="quitar-detalle r-btn r-btn-ghost" style="color: var(--r-color-danger);">✕</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
        const row = container.lastElementChild;
        bindEvents();
        if (preset) {
            const sel = row.querySelector('select[name*="[producto_id]"]');
            if (preset.producto_id) sel.value = preset.producto_id;
            if (preset.precio != null) row.querySelector('.precio-input').value = parseFloat(preset.precio).toFixed(2);
            row.querySelector('.cantidad-input').dispatchEvent(new Event('input'));
        }
        return row;
    }

    function agregarFilaPago(idx) {
        const options = metodosPago.map(mp => `<option value="${mp.id}">${mp.nombre}</option>`).join('');
        const html = `
            <div class="pago-row r-flex r-items-center r-gap-3 r-card-flat">
                <div style="flex:1; min-width:180px;"><label class="r-caption">Método</label>
                    <select name="metodos_pago[${idx}][metodo_pago_id]" class="r-select" required><option value="">— Seleccioná —</option>${options}</select></div>
                <div style="width:130px;"><label class="r-caption">Monto</label>
                    <input type="number" name="metodos_pago[${idx}][monto]" value="0.00" step="0.01" min="0" class="r-input pago-monto" required></div>
                <div style="flex:1; min-width:150px;"><label class="r-caption">Referencia</label>
                    <input type="text" name="metodos_pago[${idx}][referencia]" class="r-input" placeholder="Opcional"></div>
                <button type="button" class="quitar-pago r-btn r-btn-ghost" style="color: var(--r-color-danger);">✕</button>
            </div>`;
        pagosContainer.insertAdjacentHTML('beforeend', html);
        bindPagoEvents();
    }

    function bindEvents() {
        container.querySelectorAll('select[name*="[producto_id]"]').forEach(sel => {
            sel.onchange = function () {
                const prod = productos.find(p => p.id == this.value);
                const precioInput = this.closest('.detalle-row').querySelector('.precio-input');
                if (prod && !parseFloat(precioInput.value)) {
                    precioInput.value = parseFloat(prod.precio_compra).toFixed(2);
                    this.closest('.detalle-row').querySelector('.cantidad-input').dispatchEvent(new Event('input'));
                }
            };
        });
        container.querySelectorAll('.quitar-detalle').forEach(btn => {
            btn.onclick = function () { this.closest('.detalle-row').remove(); recalcular(); };
        });
        container.querySelectorAll('.cantidad-input, .precio-input').forEach(input => {
            input.oninput = function () {
                const row = this.closest('.detalle-row');
                const cantidad = parseFloat(row.querySelector('.cantidad-input').value) || 0;
                const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
                row.querySelector('.subtotal-display').value = fmt(cantidad * precio);
                recalcular();
            };
        });
    }

    function bindPagoEvents() {
        pagosContainer.querySelectorAll('.quitar-pago').forEach(btn => {
            btn.onclick = function () { this.closest('.pago-row').remove(); recalcular(); };
        });
        pagosContainer.querySelectorAll('.pago-monto').forEach(i => i.oninput = recalcular);
    }

    function recalcular() {
        let subtotal = 0;
        container.querySelectorAll('.detalle-row').forEach(row => {
            const c = parseFloat(row.querySelector('.cantidad-input')?.value) || 0;
            const p = parseFloat(row.querySelector('.precio-input')?.value) || 0;
            subtotal += c * p;
        });
        const dTipo = document.getElementById('descuento_tipo').value;
        const dVal = parseFloat(document.getElementById('descuento').value) || 0;
        let descuento = dTipo === 'porcentaje' ? (subtotal * dVal / 100) : dVal;
        descuento = Math.min(descuento, subtotal);
        const base = subtotal - descuento;
        const impuesto = ivaHabilitado ? (base * ivaPorcentaje / 100) : 0;
        const total = base + impuesto;
        let pagado = 0;
        pagosContainer.querySelectorAll('.pago-monto').forEach(i => pagado += parseFloat(i.value) || 0);

        document.getElementById('resumen-subtotal').textContent = fmt(subtotal);
        document.getElementById('resumen-descuento').textContent = '-' + fmt(descuento);
        document.getElementById('resumen-impuesto').textContent = fmt(impuesto);
        document.getElementById('total-compra').textContent = fmt(total);
        document.getElementById('resumen-pagado').textContent = fmt(pagado);
    }

    document.getElementById('descuento_tipo').addEventListener('change', recalcular);
    document.getElementById('descuento').addEventListener('input', recalcular);

    bindEvents();
    bindPagoEvents();
    recalcular();
});
</script>
@endsection
