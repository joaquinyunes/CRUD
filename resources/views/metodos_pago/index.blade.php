@extends('layouts.app')

@section('title', 'Métodos de Pago — ' . config('app.name'))
@section('page_title', 'Métodos de Pago')

@section('content')

<div class="r-flex r-items-center r-gap-4 r-mb-6" data-reveal="fade-up">
    <span class="r-tag" style="font-size:0.65rem; padding:3px 12px;">Configuración</span>
    <h3 class="r-caption" style="margin:0;">Gestión de métodos de pago aceptados</h3>
</div>

<div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.1">
    <h3 class="r-caption r-mb-4">Nuevo Método de Pago</h3>
    <div class="r-card-flat">
        <form id="form-crear-metodo" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:var(--space-4); align-items:end;">
            <div>
                <label class="r-label">Nombre</label>
                <input type="text" id="m-nombre" class="r-input" placeholder="Ej: Efectivo" required>
            </div>
            <div>
                <label class="r-label">Código</label>
                <input type="text" id="m-codigo" class="r-input" placeholder="Ej: EFEC" maxlength="20" required>
            </div>
            <div>
                <label class="r-label">Orden</label>
                <input type="number" id="m-orden" class="r-input" value="0" min="0">
            </div>
            <div class="r-flex r-gap-4">
                <label class="r-label" style="display:flex; align-items:center; gap:6px; margin:0;">
                    <input type="checkbox" id="m-permite_vuelto" checked> Permite vuelto
                </label>
            </div>
            <button type="submit" class="r-btn r-btn-accent" style="height:40px;">Crear</button>
        </form>
    </div>
</div>

<div data-reveal="fade-up" data-reveal-delay="0.2">
    <h3 class="r-caption r-mb-4">Listado de Métodos de Pago</h3>
    <div class="r-card-flat" style="overflow-x:auto;">
        <table class="r-table" id="tabla-metodos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Activo</th>
                    <th>Vuelto</th>
                    <th>Orden</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($metodos as $metodo)
                    <tr data-id="{{ $metodo->id }}">
                        <td><span class="m-nombre-text">{{ $metodo->nombre }}</span></td>
                        <td><span class="r-tag" style="font-size:0.65rem;">{{ $metodo->codigo }}</span></td>
                        <td>
                            <button class="toggle-activo r-btn-ghost r-btn-sm" data-id="{{ $metodo->id }}" data-activo="{{ $metodo->activo ? '1' : '0' }}">
                                @if($metodo->activo)
                                    <span class="r-tag" style="background:var(--color-sage-muted); color:var(--color-ink); font-size:0.6rem;">Activo</span>
                                @else
                                    <span class="r-tag" style="background:var(--color-sand-muted); color:var(--color-ink-soft); font-size:0.6rem;">Inactivo</span>
                                @endif
                            </button>
                        </td>
                        <td>
                            @if($metodo->permite_vuelto)
                                <span class="r-tag" style="background:var(--color-sage-muted); color:var(--color-ink); font-size:0.6rem;">Sí</span>
                            @else
                                <span class="r-tag" style="background:var(--color-sand-muted); color:var(--color-ink-soft); font-size:0.6rem;">No</span>
                            @endif
                        </td>
                        <td>{{ $metodo->orden }}</td>
                        <td>
                            <div class="r-flex r-gap-2">
                                <button class="btn-editar r-btn r-btn-sm r-btn-ghost" data-id="{{ $metodo->id }}" data-nombre="{{ $metodo->nombre }}" data-codigo="{{ $metodo->codigo }}" data-orden="{{ $metodo->orden }}" data-permite_vuelto="{{ $metodo->permite_vuelto ? '1' : '0' }}" title="Editar">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="btn-eliminar r-btn r-btn-sm r-btn-ghost" data-id="{{ $metodo->id }}" data-nombre="{{ $metodo->nombre }}" title="Eliminar" style="color:var(--color-rose);">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="row-empty">
                        <td colspan="6" class="r-text-center r-caption" style="padding:var(--space-8); color:var(--color-ink-soft);">No hay métodos de pago registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="modal-editar" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px);">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:var(--color-surface); border-radius:var(--radius-lg); padding:var(--space-6); width:90%; max-width:460px; box-shadow:var(--shadow-lg);">
        <h3 class="r-caption r-mb-4">Editar Método de Pago</h3>
        <form id="form-editar-metodo">
            <input type="hidden" id="e-id">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4);" class="r-mb-4">
                <div>
                    <label class="r-label">Nombre</label>
                    <input type="text" id="e-nombre" class="r-input" required>
                </div>
                <div>
                    <label class="r-label">Código</label>
                    <input type="text" id="e-codigo" class="r-input" maxlength="20" required>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-4);" class="r-mb-4">
                <div>
                    <label class="r-label">Orden</label>
                    <input type="number" id="e-orden" class="r-input" min="0">
                </div>
                <div>
                    <label class="r-label">Permite vuelto</label>
                    <select id="e-permite_vuelto" class="r-select" style="width:100%;">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="r-flex r-gap-4 r-justify-end">
                <button type="button" class="r-btn r-btn-ghost" id="btn-cancelar-editar">Cancelar</button>
                <button type="submit" class="r-btn r-btn-accent">Guardar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const headers = { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json' };

    function showErrors(msg) { alert(msg || 'Ocurrió un error.'); }

    // Crear
    document.getElementById('form-crear-metodo').addEventListener('submit', async function(e) {
        e.preventDefault();
        const nombre = document.getElementById('m-nombre').value.trim();
        const codigo = document.getElementById('m-codigo').value.trim();
        const orden = parseInt(document.getElementById('m-orden').value) || 0;
        const permite_vuelto = document.getElementById('m-permite_vuelto').checked;
        if (!nombre || !codigo) return showErrors('Completa todos los campos.');

        try {
            const res = await fetch('{{ route("metodos_pago.store") }}', {
                method: 'POST',
                headers,
                body: JSON.stringify({ nombre, codigo, orden, permite_vuelto })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error al crear.');
            location.reload();
        } catch(err) { showErrors(err.message); }
    });

    // Toggle activo
    document.querySelectorAll('.toggle-activo').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            try {
                const res = await fetch(`/metodos-pago/${id}/toggle`, {
                    method: 'PATCH',
                    headers
                });
                if (!res.ok) throw new Error('Error al cambiar estado.');
                location.reload();
            } catch(err) { showErrors(err.message); }
        });
    });

    // Editar - abrir modal
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('e-id').value = this.dataset.id;
            document.getElementById('e-nombre').value = this.dataset.nombre;
            document.getElementById('e-codigo').value = this.dataset.codigo;
            document.getElementById('e-orden').value = this.dataset.orden;
            document.getElementById('e-permite_vuelto').value = this.dataset.permite_vuelto;
            document.getElementById('modal-editar').style.display = 'block';
        });
    });

    document.getElementById('btn-cancelar-editar').addEventListener('click', () => {
        document.getElementById('modal-editar').style.display = 'none';
    });

    document.getElementById('modal-editar').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });

    // Editar - guardar
    document.getElementById('form-editar-metodo').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('e-id').value;
        const nombre = document.getElementById('e-nombre').value.trim();
        const codigo = document.getElementById('e-codigo').value.trim();
        const orden = parseInt(document.getElementById('e-orden').value) || 0;
        const permite_vuelto = document.getElementById('e-permite_vuelto').value === '1';
        if (!nombre || !codigo) return showErrors('Completa todos los campos.');

        try {
            const res = await fetch(`/metodos-pago/${id}`, {
                method: 'PUT',
                headers,
                body: JSON.stringify({ nombre, codigo, orden, permite_vuelto })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error al actualizar.');
            location.reload();
        } catch(err) { showErrors(err.message); }
    });

    // Eliminar
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm(`¿Eliminar el método "${this.dataset.nombre}"?`)) return;
            const id = this.dataset.id;
            try {
                const res = await fetch(`/metodos-pago/${id}`, {
                    method: 'DELETE',
                    headers
                });
                if (!res.ok) throw new Error('Error al eliminar.');
                location.reload();
            } catch(err) { showErrors(err.message); }
        });
    });
})();
</script>
@endpush
