@extends('layouts.app')

@section('page_title', isset($orden) ? 'Editar orden de compra' : 'Nueva orden de compra')

@section('content')
<div class="py-6"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

    <div class="r-flex r-items-center r-justify-between r-mb-6">
        <h1 class="r-display-m">{{ isset($orden) ? 'Editar orden de compra' : 'Nueva orden de compra' }}</h1>
        <a href="{{ route('ordenes-compra.index') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
    </div>

    @if($errors->any())
        <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" id="oc-form"
          action="{{ isset($orden) ? route('ordenes-compra.update', $orden) : route('ordenes-compra.store') }}"
          class="space-y-6">
        @csrf
        @isset($orden)@method('PUT')@endisset

        <div class="r-card-flat">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <label class="r-label">Proveedor *</label>
                    <select name="proveedor_id" class="r-select" required>
                        <option value="">— Seleccioná —</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}" {{ old('proveedor_id', $orden->proveedor_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label class="r-label">Fecha *</label>
                    <input type="date" name="fecha" class="r-input" required value="{{ old('fecha', isset($orden) ? $orden->fecha->format('Y-m-d') : date('Y-m-d')) }}"></div>
                <div><label class="r-label">Entrega estimada</label>
                    <input type="date" name="fecha_entrega_estimada" class="r-input" value="{{ old('fecha_entrega_estimada', isset($orden) && $orden->fecha_entrega_estimada ? $orden->fecha_entrega_estimada->format('Y-m-d') : '') }}"></div>
                <div><label class="r-label">Estado</label>
                    <select name="estado" class="r-select">
                        <option value="borrador" {{ old('estado', $orden->estado ?? 'borrador') === 'borrador' ? 'selected' : '' }}>Borrador</option>
                        <option value="enviada" {{ old('estado', $orden->estado ?? '') === 'enviada' ? 'selected' : '' }}>Enviada</option>
                    </select>
                </div>
                @if(($depositos ?? collect())->count() > 1)
                <div><label class="r-label">Depósito destino</label>
                    <select name="deposito_id" class="r-select">
                        @foreach($depositos as $dep)
                            <option value="{{ $dep->id }}" {{ old('deposito_id', $orden->deposito_id ?? '') == $dep->id ? 'selected' : '' }}>{{ $dep->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        <div class="r-card-flat">
            <div class="r-flex r-items-center r-justify-between r-mb-4">
                <h2 class="r-label">Ítems a pedir</h2>
                <button type="button" id="add-row" class="r-btn r-btn-primary r-btn-sm">+ Agregar ítem</button>
            </div>
            <div id="rows" class="space-y-3">
                @foreach(old('detalles', isset($orden) ? $orden->detalles->toArray() : []) as $i => $d)
                    <div class="row r-flex r-gap-3 r-items-end" style="flex-wrap:wrap; padding:.75rem; background:var(--r-color-bg-alt); border-radius:8px;">
                        <div style="flex:1; min-width:200px;"><label class="r-caption">Producto</label>
                            <select name="detalles[{{ $i }}][producto_id]" class="r-select prod" required>
                                <option value="">—</option>
                                @foreach($productos as $p)
                                    <option value="{{ $p->id }}" data-precio="{{ $p->precio_compra }}" {{ ($d['producto_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                                @endforeach
                            </select></div>
                        <div style="width:90px;"><label class="r-caption">Cant.</label><input type="number" name="detalles[{{ $i }}][cantidad]" value="{{ $d['cantidad'] ?? 1 }}" min="1" class="r-input cant" required></div>
                        <div style="width:120px;"><label class="r-caption">Precio</label><input type="number" step="0.01" min="0" name="detalles[{{ $i }}][precio]" value="{{ $d['precio'] ?? '0' }}" class="r-input prec" required></div>
                        <button type="button" class="del r-btn r-btn-ghost" style="color:#dc2626;">✕</button>
                    </div>
                @endforeach
            </div>
            <div class="r-flex r-justify-end r-mt-4"><span>Total estimado: <strong id="r-tot" class="r-display-m">$0</strong></span></div>
        </div>

        <div class="r-card-flat">
            <label class="r-label">Observaciones</label>
            <textarea name="observaciones" rows="2" class="r-input">{{ old('observaciones', $orden->observaciones ?? '') }}</textarea>
        </div>

        <div class="r-flex r-justify-end r-gap-3">
            <a href="{{ route('ordenes-compra.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button class="r-btn r-btn-primary">Guardar</button>
        </div>
    </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('rows');
    const productos = @json($productos->map(fn($p) => ['id'=>$p->id,'nombre'=>$p->nombre,'precio'=>$p->precio_compra]));
    let idx = rows.querySelectorAll('.row').length;
    const fmt = v => '$' + v.toFixed(2);

    document.getElementById('add-row').onclick = () => {
        const opts = productos.map(p => `<option value="${p.id}" data-precio="${p.precio}">${p.nombre}</option>`).join('');
        rows.insertAdjacentHTML('beforeend', `
        <div class="row r-flex r-gap-3 r-items-end" style="flex-wrap:wrap; padding:.75rem; background:var(--r-color-bg-alt); border-radius:8px;">
            <div style="flex:1; min-width:200px;"><label class="r-caption">Producto</label>
                <select name="detalles[${idx}][producto_id]" class="r-select prod" required><option value="">—</option>${opts}</select></div>
            <div style="width:90px;"><label class="r-caption">Cant.</label><input type="number" name="detalles[${idx}][cantidad]" value="1" min="1" class="r-input cant" required></div>
            <div style="width:120px;"><label class="r-caption">Precio</label><input type="number" step="0.01" min="0" name="detalles[${idx}][precio]" value="0" class="r-input prec" required></div>
            <button type="button" class="del r-btn r-btn-ghost" style="color:#dc2626;">✕</button>
        </div>`);
        idx++; bind();
    };
    function bind() {
        rows.querySelectorAll('.del').forEach(b => b.onclick = function () { this.closest('.row').remove(); calc(); });
        rows.querySelectorAll('.prod').forEach(s => s.onchange = function () {
            const o = this.selectedOptions[0];
            const prec = this.closest('.row').querySelector('.prec');
            if (o && o.dataset.precio && !parseFloat(prec.value)) prec.value = parseFloat(o.dataset.precio).toFixed(2);
            calc();
        });
        rows.querySelectorAll('.cant, .prec').forEach(i => i.oninput = calc);
    }
    function calc() {
        let t = 0;
        rows.querySelectorAll('.row').forEach(r => t += (parseFloat(r.querySelector('.cant').value) || 0) * (parseFloat(r.querySelector('.prec').value) || 0));
        document.getElementById('r-tot').textContent = fmt(t);
    }
    bind(); calc();
});
</script>
@endsection
