@extends('layouts.app')

@section('title', 'Unidades de Medida — ' . config('app.name'))
@section('page_title', 'Unidades de Medida')

@section('content')

<div class="r-flex r-items-center r-gap-4 r-mb-6" data-reveal="fade-up">
    <span class="r-tag" style="font-size:0.65rem; padding:3px 12px;">Configuración</span>
    <h3 class="r-caption" style="margin:0;">Gestión de unidades de medida para el sistema</h3>
</div>

<div class="r-mb-8" data-reveal="fade-up" data-reveal-delay="0.1">
    <h3 class="r-caption r-mb-4">Nueva Unidad de Medida</h3>
    <div class="r-card-flat">
        <form id="form-crear-unidad" class="r-flex r-gap-4 r-items-end" style="flex-wrap:wrap;">
            <div style="flex:1; min-width:180px;">
                <label class="r-label">Nombre</label>
                <input type="text" id="u-nombre" class="r-input" placeholder="Ej: Kilogramo" required>
            </div>
            <div style="flex:0 0 160px;">
                <label class="r-label">Abreviación</label>
                <input type="text" id="u-abreviacion" class="r-input" placeholder="Ej: kg" maxlength="10" required>
            </div>
            <button type="submit" class="r-btn r-btn-accent" style="height:40px;">Crear</button>
        </form>
    </div>
</div>

<div data-reveal="fade-up" data-reveal-delay="0.2">
    <h3 class="r-caption r-mb-4">Listado de Unidades</h3>
    <div class="r-card-flat" style="overflow-x:auto;">
        <table class="r-table" id="tabla-unidades">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Abreviación</th>
                    <th>Estado</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unidades as $unidad)
                    <tr data-id="{{ $unidad->id }}">
                        <td>
                            <span class="u-nombre-text">{{ $unidad->nombre }}</span>
                        </td>
                        <td>
                            <span class="u-abrev-text">{{ $unidad->abreviacion }}</span>
                        </td>
                        <td>
                            <button class="toggle-estado r-btn-ghost r-btn-sm" data-id="{{ $unidad->id }}" data-estado="{{ $unidad->estado ? '1' : '0' }}" title="Cambiar estado">
                                @if($unidad->estado)
                                    <span class="r-tag" style="background:var(--color-sage-muted); color:var(--color-ink); font-size:0.6rem;">Activo</span>
                                @else
                                    <span class="r-tag" style="background:var(--color-sand-muted); color:var(--color-ink-soft); font-size:0.6rem;">Inactivo</span>
                                @endif
                            </button>
                        </td>
                        <td>
                            <div class="r-flex r-gap-2">
                                <button class="btn-editar r-btn r-btn-sm r-btn-ghost" data-id="{{ $unidad->id }}" data-nombre="{{ $unidad->nombre }}" data-abreviacion="{{ $unidad->abreviacion }}" title="Editar">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button class="btn-eliminar r-btn r-btn-sm r-btn-ghost" data-id="{{ $unidad->id }}" data-nombre="{{ $unidad->nombre }}" title="Eliminar" style="color:var(--color-rose);">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="row-empty">
                        <td colspan="4" class="r-text-center r-caption" style="padding:var(--space-8); color:var(--color-ink-soft);">No hay unidades de medida registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="modal-editar" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px);">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:var(--color-surface); border-radius:var(--radius-lg); padding:var(--space-6); width:90%; max-width:420px; box-shadow:var(--shadow-lg);">
        <h3 class="r-caption r-mb-4">Editar Unidad de Medida</h3>
        <form id="form-editar-unidad">
            <input type="hidden" id="e-id">
            <div class="r-mb-4">
                <label class="r-label">Nombre</label>
                <input type="text" id="e-nombre" class="r-input" required>
            </div>
            <div class="r-mb-6">
                <label class="r-label">Abreviación</label>
                <input type="text" id="e-abreviacion" class="r-input" maxlength="10" required>
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

    function showErrors(msg) {
        alert(msg || 'Ocurrió un error.');
    }

    // Crear
    document.getElementById('form-crear-unidad').addEventListener('submit', async function(e) {
        e.preventDefault();
        const nombre = document.getElementById('u-nombre').value.trim();
        const abreviacion = document.getElementById('u-abreviacion').value.trim();
        if (!nombre || !abreviacion) return showErrors('Completa todos los campos.');

        try {
            const res = await fetch('{{ route("unidades_medida.store") }}', {
                method: 'POST',
                headers,
                body: JSON.stringify({ nombre, abreviacion })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error al crear.');

            document.getElementById('u-nombre').value = '';
            document.getElementById('u-abreviacion').value = '';
            location.reload();
        } catch(err) {
            showErrors(err.message);
        }
    });

    // Toggle estado
    document.querySelectorAll('.toggle-estado').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            try {
                const res = await fetch(`/unidades-medida/${id}/toggle`, {
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
            document.getElementById('e-abreviacion').value = this.dataset.abreviacion;
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
    document.getElementById('form-editar-unidad').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('e-id').value;
        const nombre = document.getElementById('e-nombre').value.trim();
        const abreviacion = document.getElementById('e-abreviacion').value.trim();
        if (!nombre || !abreviacion) return showErrors('Completa todos los campos.');

        try {
            const res = await fetch(`/unidades-medida/${id}`, {
                method: 'PUT',
                headers,
                body: JSON.stringify({ nombre, abreviacion })
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Error al actualizar.');
            location.reload();
        } catch(err) { showErrors(err.message); }
    });

    // Eliminar
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm(`¿Eliminar la unidad "${this.dataset.nombre}"?`)) return;
            const id = this.dataset.id;
            try {
                const res = await fetch(`/unidades-medida/${id}`, {
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
