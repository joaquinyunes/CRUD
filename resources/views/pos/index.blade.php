@extends('layouts.app')

@section('page_title', 'Punto de venta')
@section('title', 'POS · ' . config('app.name'))

@section('content')
<div id="pos"
     data-buscar-url="{{ route('pos.buscar') }}"
     data-vender-url="{{ route('pos.store') }}"
     data-simbolo="{{ $simbolo }}"
     data-caja-abierta="{{ $sesion ? '1' : '0' }}">

    @if(! $sesion)
        <div class="r-card-flat" style="max-width:460px;margin:2rem auto;text-align:center;">
            <h2 class="r-display-m r-mb-3">Caja cerrada</h2>
            <p class="r-body r-mb-4">Necesitás una caja abierta para operar el punto de venta.</p>
            <a href="{{ route('caja.index') }}" class="r-btn r-btn-primary">Ir a caja</a>
        </div>
    @else
    <div x-data="posApp()" x-init="init()" class="pos-grid" @keydown.window="hotkeys($event)">

        {{-- Columna izquierda: búsqueda + carrito --}}
        <section class="pos-col">
            <div class="r-flex r-gap-2 r-mb-3">
                <input x-ref="buscar" x-model="term" @input.debounce.200ms="buscar()" @keydown.enter.prevent="agregarPrimero()"
                       class="r-input" style="font-size:1.1rem;" placeholder="Código de barras o nombre  (F2)" autocomplete="off">
                <button class="r-btn r-btn-ghost" @click="term=''; resultados=[]; $refs.buscar.focus()">Limpiar</button>
            </div>

            <template x-if="resultados.length">
                <div class="pos-resultados">
                    <template x-for="p in resultados" :key="p.id">
                        <button class="pos-resultado" @click="agregar(p)">
                            <span x-text="p.nombre"></span>
                            <span class="r-mono" x-text="money(p.precio)"></span>
                        </button>
                    </template>
                </div>
            </template>

            <table class="pos-carrito">
                <thead><tr><th>Producto</th><th style="width:6rem">Cant.</th><th style="width:7rem">Precio</th><th style="width:7rem">Subtotal</th><th></th></tr></thead>
                <tbody>
                    <template x-for="(item, i) in items" :key="item.producto_id">
                        <tr>
                            <td x-text="item.nombre"></td>
                            <td><input type="number" min="0.001" step="0.001" class="r-input r-input-sm" x-model.number="item.cantidad" @change="recalcular()"></td>
                            <td><input type="number" min="0" step="0.01" class="r-input r-input-sm" x-model.number="item.precio" @change="recalcular()"></td>
                            <td class="r-mono" x-text="money(item.cantidad * item.precio)"></td>
                            <td><button class="r-btn r-btn-ghost r-btn-sm" @click="quitar(i)">&times;</button></td>
                        </tr>
                    </template>
                    <template x-if="!items.length">
                        <tr><td colspan="5" style="text-align:center;color:var(--color-ink-soft);padding:2rem">Carrito vacío</td></tr>
                    </template>
                </tbody>
            </table>
        </section>

        {{-- Columna derecha: totales + cobro --}}
        <aside class="pos-col pos-cobro">
            <div class="pos-total">
                <span>TOTAL</span>
                <span class="r-mono" x-text="money(total)"></span>
            </div>
            <div class="r-caption" style="text-transform:none;letter-spacing:0">
                Subtotal <span class="r-mono" x-text="money(subtotal)"></span> ·
                IVA <span class="r-mono" x-text="money(impuesto)"></span>
            </div>

            <div class="r-mt-3">
                <label class="r-label">Cliente</label>
                <select class="r-input" x-model.number="cliente_id">
                    <option :value="null">Consumidor final</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}">{{ trim($c->nombre.' '.$c->apellido) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="r-flex r-gap-2 r-mt-3">
                <div style="flex:1">
                    <label class="r-label">Descuento</label>
                    <input type="number" min="0" step="0.01" class="r-input" x-model.number="descuento" @change="recalcular()">
                </div>
                <div>
                    <label class="r-label">Tipo</label>
                    <select class="r-input" x-model="descuento_tipo" @change="recalcular()">
                        <option value="fijo">$</option>
                        <option value="porcentaje">%</option>
                    </select>
                </div>
            </div>

            <div class="r-mt-3">
                <label class="r-label">Pagos</label>
                <template x-for="(pago, i) in pagos" :key="i">
                    <div class="r-flex r-gap-2 r-mb-2">
                        <select class="r-input" x-model.number="pago.metodo_pago_id">
                            @foreach($metodosPago as $m)
                                <option value="{{ $m->id }}" data-codigo="{{ $m->codigo }}">{{ $m->nombre }}</option>
                            @endforeach
                        </select>
                        <input type="number" min="0" step="0.01" class="r-input" style="width:8rem" x-model.number="pago.monto">
                        <template x-if="pagos.length>1">
                            <button class="r-btn r-btn-ghost r-btn-sm" @click="pagos.splice(i,1)">&times;</button>
                        </template>
                    </div>
                </template>
                <div class="r-flex r-gap-2">
                    <button class="r-btn r-btn-ghost r-btn-sm" @click="agregarPago()">+ medio</button>
                    <button class="r-btn r-btn-ghost r-btn-sm" @click="pagoExacto()">Exacto</button>
                </div>
            </div>

            <div class="r-mt-3">
                <label class="r-label">Recibido (efectivo)</label>
                <input type="number" min="0" step="0.01" class="r-input" x-model.number="recibido">
                <template x-if="vuelto>0">
                    <div class="pos-vuelto">Vuelto <span class="r-mono" x-text="money(vuelto)"></span></div>
                </template>
            </div>

            <template x-if="error">
                <p class="r-flash-error r-mt-3" x-text="error"></p>
            </template>

            <button class="r-btn r-btn-primary r-mt-4" style="width:100%;font-size:1.1rem;padding:0.9rem"
                    :disabled="procesando || !items.length" @click="cobrar()">
                <span x-text="procesando ? 'Procesando…' : 'Cobrar  (F12)'"></span>
            </button>
        </aside>
    </div>
    @endif
</div>

<style>
    .pos-grid{display:grid;grid-template-columns:1fr 22rem;gap:1.5rem;align-items:start}
    @media(max-width:900px){.pos-grid{grid-template-columns:1fr}}
    .pos-col{min-width:0}
    .pos-cobro{position:sticky;top:1rem;background:var(--color-surface,#fff);border:1px solid var(--color-line);border-radius:14px;padding:1.25rem}
    .pos-resultados{border:1px solid var(--color-line);border-radius:10px;margin-bottom:1rem;max-height:14rem;overflow:auto}
    .pos-resultado{display:flex;justify-content:space-between;width:100%;padding:0.6rem 0.9rem;border:0;border-bottom:1px solid var(--color-line);background:transparent;cursor:pointer;text-align:left}
    .pos-resultado:hover{background:var(--color-paper)}
    .pos-carrito{width:100%;border-collapse:collapse}
    .pos-carrito th{text-align:left;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--color-ink-soft);padding:0.5rem}
    .pos-carrito td{padding:0.4rem 0.5rem;border-bottom:1px solid var(--color-line)}
    .r-input-sm{padding:0.25rem 0.4rem;font-size:0.85rem}
    .pos-total{display:flex;justify-content:space-between;align-items:baseline;font-size:1.6rem;font-weight:700}
    .pos-vuelto{margin-top:0.5rem;font-weight:700;color:var(--color-forest,#3f5135)}
</style>
@endsection

@section('scripts')
<script>
window.posApp = function () {
    const el = document.getElementById('pos');
    const simbolo = el.dataset.simbolo || '$';
    const IVA = {{ (float) \App\Models\Setting::obtener('sistema_iva', '21') }};
    const IVA_ON = {{ \App\Models\Setting::obtener('sistema_impuesto_habilitado', '1') === '1' ? 'true' : 'false' }};
    const metodos = @json($metodosPago->map(fn($m)=>['id'=>$m->id,'codigo'=>$m->codigo]));
    const efectivoId = (metodos.find(m => m.codigo === 'efectivo') || {}).id || metodos[0]?.id;

    return {
        term: '', resultados: [], items: [],
        cliente_id: null, descuento: 0, descuento_tipo: 'fijo',
        pagos: [{ metodo_pago_id: efectivoId, monto: 0 }],
        recibido: 0, subtotal: 0, impuesto: 0, total: 0,
        error: '', procesando: false,

        init() { this.$refs.buscar && this.$refs.buscar.focus(); },

        money(n) { return simbolo + ' ' + (Number(n) || 0).toFixed(2); },

        async buscar() {
            if (this.term.trim().length < 2) { this.resultados = []; return; }
            const r = await fetch(el.dataset.buscarUrl + '?q=' + encodeURIComponent(this.term), { headers: { 'Accept': 'application/json' } });
            this.resultados = r.ok ? await r.json() : [];
        },
        agregarPrimero() {
            if (this.resultados.length) this.agregar(this.resultados[0]);
        },
        agregar(p) {
            const existente = this.items.find(i => i.producto_id === p.id);
            const cant = p.factor && p.factor !== 1 ? p.factor : 1;
            if (existente) existente.cantidad += cant;
            else this.items.push({ producto_id: p.id, nombre: p.nombre, cantidad: cant, precio: p.precio });
            this.term = ''; this.resultados = [];
            this.recalcular();
            this.$refs.buscar.focus();
        },
        quitar(i) { this.items.splice(i, 1); this.recalcular(); },

        recalcular() {
            this.subtotal = this.items.reduce((s, i) => s + (Number(i.cantidad) || 0) * (Number(i.precio) || 0), 0);
            let desc = this.descuento_tipo === 'porcentaje' ? this.subtotal * this.descuento / 100 : Number(this.descuento) || 0;
            desc = Math.min(Math.max(desc, 0), this.subtotal);
            const base = this.subtotal - desc;
            this.impuesto = IVA_ON ? base * IVA / 100 : 0;
            this.total = Math.round((base + this.impuesto) * 100) / 100;
            if (this.pagos.length === 1) { this.pagos[0].monto = this.total; this.recibido = this.total; }
        },
        agregarPago() { this.pagos.push({ metodo_pago_id: efectivoId, monto: 0 }); },
        pagoExacto() { this.pagos = [{ metodo_pago_id: efectivoId, monto: this.total }]; this.recibido = this.total; },

        get vuelto() {
            const ef = this.pagos.filter(p => p.metodo_pago_id === efectivoId).reduce((s, p) => s + (Number(p.monto) || 0), 0);
            return Math.max(0, Math.round(((Number(this.recibido) || 0) - ef) * 100) / 100);
        },

        hotkeys(e) {
            if (e.key === 'F2') { e.preventDefault(); this.$refs.buscar.focus(); }
            if (e.key === 'F12') { e.preventDefault(); this.cobrar(); }
        },

        async cobrar() {
            if (this.procesando || !this.items.length) return;
            this.error = ''; this.procesando = true;
            let pin = null;
            if (this.descuento_tipo === 'porcentaje' && Number(this.descuento) > {{ (float) \App\Models\Setting::obtener('ventas_limite_descuento', '10') }}) {
                pin = prompt('Descuento alto. PIN de supervisor:');
            }
            try {
                const r = await fetch(el.dataset.venderUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({
                        cliente_id: this.cliente_id || null,
                        items: this.items.map(i => ({ producto_id: i.producto_id, cantidad: i.cantidad, precio: i.precio })),
                        descuento: this.descuento || 0, descuento_tipo: this.descuento_tipo,
                        pagos: this.pagos.filter(p => p.monto > 0),
                        recibido: this.recibido || 0,
                        pin_supervisor: pin,
                    }),
                });
                const data = await r.json();
                if (!r.ok) { this.error = data.message || 'No se pudo registrar la venta.'; this.procesando = false; return; }
                window.RhythmToast?.success('Venta ' + data.numero + ' · vuelto ' + this.money(data.vuelto));
                window.open(data.ticket_url, '_blank', 'width=380,height=640');
                this.reset();
            } catch (err) {
                this.error = 'Error de conexión.';
            }
            this.procesando = false;
        },
        reset() {
            this.items = []; this.term = ''; this.resultados = [];
            this.cliente_id = null; this.descuento = 0; this.descuento_tipo = 'fijo';
            this.pagos = [{ metodo_pago_id: efectivoId, monto: 0 }]; this.recibido = 0;
            this.recalcular();
            this.$refs.buscar.focus();
        },
    };
};
</script>
@endsection
