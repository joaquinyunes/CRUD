@extends('layouts.app')

@section('page_title', isset($presupuesto) ? 'Editar presupuesto' : 'Nuevo presupuesto')

@section('content')
<div class="py-6"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">{{ isset($presupuesto) ? 'Editar presupuesto' : 'Nuevo presupuesto' }}</h1>
        <a href="{{ route('presupuestos.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
    </div>

    @if($errors->any())
        <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" id="pre-form"
          action="{{ isset($presupuesto) ? route('presupuestos.update', $presupuesto) : route('presupuestos.store') }}"
          class="space-y-6">
        @csrf
        @isset($presupuesto)@method('PUT')@endisset

        <div class="r-card-flat">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="r-label">Cliente *</label>
                    <x-buscador name="cliente_id"
                                :url="route('clientes.buscar')"
                                :valor="old('cliente_id', $presupuesto->cliente_id ?? null)"
                                :etiqueta="$clienteElegido?->etiquetaCorta()"
                                placeholder="Apellido, nombre o documento…"
                                requerido />
                </div>
                <div>
                    <label class="r-label">Fecha *</label>
                    <input type="date" name="fecha" class="r-input" required
                           value="{{ old('fecha', isset($presupuesto) ? $presupuesto->fecha->format('Y-m-d') : date('Y-m-d')) }}">
                </div>
                <div>
                    <label class="r-label">Validez (días)</label>
                    <input type="number" name="validez_dias" class="r-input" min="1" value="{{ old('validez_dias', $presupuesto->validez_dias ?? 15) }}">
                </div>
                <div>
                    <label class="r-label">Estado</label>
                    <select name="estado" class="r-select">
                        @foreach(['borrador'=>'Borrador','enviado'=>'Enviado','aceptado'=>'Aceptado','rechazado'=>'Rechazado'] as $k=>$v)
                            <option value="{{ $k }}" {{ old('estado', $presupuesto->estado ?? 'borrador') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="r-card-flat">
            <div class="r-flex r-items-center r-justify-between r-mb-4">
                <h2 class="r-label">Ítems</h2>
                <button type="button" id="add-row" class="r-btn r-btn-primary r-btn-sm">+ Agregar ítem</button>
            </div>
            <div id="rows" class="space-y-3">
                @foreach(old('detalles', isset($presupuesto) ? $presupuesto->detalles->toArray() : []) as $i => $d)
                    <div class="row r-flex r-gap-3 r-items-end" style="flex-wrap:wrap; padding:.75rem; background:var(--r-color-bg-alt); border-radius:8px;">
                        <div style="flex:1; min-width:200px;">
                            <label class="r-caption">Producto</label>
                            <x-buscador :name="'detalles['.$i.'][producto_id]'"
                                        :url="route('productos.buscar')"
                                        :valor="$d['producto_id'] ?? null"
                                        :etiqueta="$nombresProducto[$d['producto_id'] ?? 0] ?? null"
                                        placeholder="Nombre, código o marca…"
                                        al-elegir="producto-elegido"
                                        requerido />
                        </div>
                        <div style="width:90px;"><label class="r-caption">Cant.</label><input type="number" name="detalles[{{ $i }}][cantidad]" value="{{ $d['cantidad'] ?? 1 }}" min="1" class="r-input cant" required></div>
                        <div style="width:120px;"><label class="r-caption">Precio</label><input type="number" step="0.01" min="0" name="detalles[{{ $i }}][precio]" value="{{ $d['precio'] ?? '0' }}" class="r-input prec" required></div>
                        <button type="button" class="del r-btn r-btn-ghost" style="color:#dc2626;">✕</button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="r-card-flat">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 r-mb-3">
                <div>
                    <label class="r-label">Tipo de descuento</label>
                    <select name="descuento_tipo" id="dtipo" class="r-select">
                        <option value="fijo" {{ old('descuento_tipo', $presupuesto->descuento_tipo ?? 'fijo') === 'fijo' ? 'selected' : '' }}>Monto fijo</option>
                        <option value="porcentaje" {{ old('descuento_tipo', $presupuesto->descuento_tipo ?? '') === 'porcentaje' ? 'selected' : '' }}>Porcentaje</option>
                    </select>
                </div>
                <div><label class="r-label">Descuento</label><input type="number" step="0.01" min="0" name="descuento" id="dval" class="r-input" value="{{ old('descuento', $presupuesto->descuento ?? 0) }}"></div>
            </div>
            <label class="r-label">Observaciones</label>
            <textarea name="observaciones" rows="2" class="r-input">{{ old('observaciones', $presupuesto->observaciones ?? '') }}</textarea>
            <div class="r-flex r-justify-end r-gap-4 r-mt-4" style="flex-wrap:wrap;">
                <span>Subtotal: <strong id="r-sub">$0</strong></span>
                <span>Impuesto: <strong id="r-imp">$0</strong></span>
                <span>Total: <strong id="r-tot" class="r-display-m">$0</strong></span>
            </div>
        </div>

        <div class="r-flex r-justify-end r-gap-3">
            <a href="{{ route('presupuestos.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button class="r-btn r-btn-primary">Guardar</button>
        </div>
    </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('rows');
    const urlProductos = @json(route('productos.buscar'));
    const iva = {{ \App\Models\Setting::obtener('sistema_iva', '21') }};
    const ivaOn = {{ \App\Models\Setting::obtener('sistema_impuesto_habilitado', '1') === '1' ? 'true' : 'false' }};
    let idx = rows.querySelectorAll('.row').length;
    const fmt = v => '$' + v.toFixed(2);

    document.getElementById('add-row').onclick = () => {
        rows.insertAdjacentHTML('beforeend', `
        <div class="row r-flex r-gap-3 r-items-end" style="flex-wrap:wrap; padding:.75rem; background:var(--r-color-bg-alt); border-radius:8px;">
            <div style="flex:1; min-width:200px;"><label class="r-caption">Producto</label>
                <div class="r-buscador" data-buscador data-url="${urlProductos}" data-al-elegir="producto-elegido">
                    <input type="hidden" name="detalles[${idx}][producto_id]" data-buscador-valor>
                    <input type="text" class="r-input" autocomplete="off" role="combobox"
                           placeholder="Nombre, código o marca…" required data-buscador-texto>
                    <ul class="r-buscador-lista" role="listbox" hidden data-buscador-lista></ul>
                </div></div>
            <div style="width:90px;"><label class="r-caption">Cant.</label><input type="number" name="detalles[${idx}][cantidad]" value="1" min="1" class="r-input cant" required></div>
            <div style="width:120px;"><label class="r-caption">Precio</label><input type="number" step="0.01" min="0" name="detalles[${idx}][precio]" value="0" class="r-input prec" required></div>
            <button type="button" class="del r-btn r-btn-ghost" style="color:#dc2626;">✕</button>
        </div>`);
        idx++; bind();
    };

    function bind() {
        rows.querySelectorAll('.del').forEach(b => b.onclick = function () { this.closest('.row').remove(); calc(); });
        rows.querySelectorAll('[data-buscador]').forEach(raiz => {
            if (raiz.dataset.precioAtado) return;
            raiz.dataset.precioAtado = '1';
            raiz.addEventListener('producto-elegido', function (e) {
                const prec = this.closest('.row').querySelector('.prec');
                if (!parseFloat(prec.value) && e.detail.precio_venta != null) {
                    prec.value = parseFloat(e.detail.precio_venta).toFixed(2);
                }
                calc();
            });
        });
        rows.querySelectorAll('.cant, .prec').forEach(i => i.oninput = calc);
    }

    function calc() {
        let sub = 0;
        rows.querySelectorAll('.row').forEach(r => {
            sub += (parseFloat(r.querySelector('.cant').value) || 0) * (parseFloat(r.querySelector('.prec').value) || 0);
        });
        const dt = document.getElementById('dtipo').value;
        const dv = parseFloat(document.getElementById('dval').value) || 0;
        let desc = dt === 'porcentaje' ? sub * dv / 100 : dv;
        desc = Math.min(desc, sub);
        const base = sub - desc;
        const imp = ivaOn ? base * iva / 100 : 0;
        document.getElementById('r-sub').textContent = fmt(sub);
        document.getElementById('r-imp').textContent = fmt(imp);
        document.getElementById('r-tot').textContent = fmt(base + imp);
    }

    document.getElementById('dtipo').onchange = calc;
    document.getElementById('dval').oninput = calc;
    bind(); calc();
});
</script>
@endsection
