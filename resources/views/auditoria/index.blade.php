@extends('layouts.app')

@section('page_title', 'Auditoría')

@section('content')

<h2 class="r-display-l r-mb-8" data-reveal="fade-up">Auditoría</h2>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('auditoria.index') }}" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:var(--space-3); align-items:flex-end;">
        <div style="grid-column:span 2;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Acción o modelo…" class="r-input">
        </div>
        <div>
            <label class="r-label">Modelo</label>
            <select name="modelo" class="r-select" style="width:100%;">
                <option value="">Todos</option>
                @foreach ($modelos as $m)
                    <option value="{{ $m }}" @selected(request('modelo') == $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="r-label">Usuario</label>
            <select name="usuario_id" class="r-select" style="width:100%;">
                <option value="">Todos</option>
                @foreach ($usuarios as $usr)
                    <option value="{{ $usr->id }}" @selected(request('usuario_id') == $usr->id)>{{ $usr->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="r-label">Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}" class="r-input" style="width:100%;">
        </div>
        <div>
            <label class="r-label">Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="r-input" style="width:100%;">
        </div>
        <div style="grid-column:1/-1;" class="r-flex r-gap-3">
            <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
            <a href="{{ route('auditoria.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        </div>
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Fecha/Hora</th>
                    <th>Usuario</th>
                    <th>IP</th>
                    <th>Acción</th>
                    <th>Modelo</th>
                    <th style="text-align:right;">ID</th>
                    <th style="text-align:right;">Detalle</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td style="white-space:nowrap; color:var(--color-ink-soft);">{{ $registro->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td style="font-weight:500;">{{ $registro->user->name ?? '—' }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $registro->ip }}</td>
                        <td>
                            @php
                                $colores = ['created' => 'r-tag-success', 'updated' => '', 'deleted' => 'r-tag-danger', 'restored' => 'r-tag-marigold'];
                                $clase = $colores[$registro->accion] ?? '';
                            @endphp
                            <span class="r-tag {{ $clase }}">{{ $registro->accion }}</span>
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $registro->modelo_afectado ?? '—' }}</td>
                        <td style="text-align:right; color:var(--color-ink-soft);">{{ $registro->modelo_id ?? '—' }}</td>
                        <td style="text-align:right;">
                            <button type="button" class="r-btn r-btn-ghost r-btn-sm btn-ver-detalle" style="font-size:0.75rem; padding:4px 12px;"
                                data-fecha="{{ $registro->created_at?->format('d/m/Y H:i:s') }}"
                                data-usuario="{{ $registro->user->name ?? '—' }}"
                                data-ip="{{ $registro->ip }}"
                                data-accion="{{ $registro->accion }}"
                                data-modelo="{{ $registro->modelo_afectado ?? '—' }}"
                                data-modelo-id="{{ $registro->modelo_id ?? '—' }}"
                                data-anterior="{{ json_encode($registro->valor_anterior) }}"
                                data-nuevo="{{ json_encode($registro->valor_nuevo) }}">
                                Ver detalle
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No hay registros de auditoría para los filtros aplicados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $registros->links() }}

@endsection

{{-- Modal --}}
<div id="modal-detalle" style="position:fixed; inset:0; background:rgba(28,36,34,0.5); display:none; z-index:50; align-items:center; justify-content:center; padding:16px;">
    <div class="r-card-flat" style="max-width:640px; width:100%; max-height:85vh; overflow-y:auto; padding:0;">
        <div class="r-flex r-items-center r-justify-between" style="padding:var(--space-4) var(--space-6); border-bottom:1px solid var(--color-line);">
            <h3 class="r-display-m" style="font-size:1.125rem;">Detalle de auditoría</h3>
            <button type="button" class="cerrar-modal" style="background:none; border:none; font-size:1.5rem; color:var(--color-ink-soft); cursor:pointer;">&times;</button>
        </div>
        <div style="padding:var(--space-4) var(--space-6); display:flex; flex-direction:column; gap:var(--space-3);">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-2); font-size:0.875rem;">
                <div><span class="r-caption">Fecha:</span> <span class="detalle-fecha"></span></div>
                <div><span class="r-caption">Usuario:</span> <span class="detalle-usuario"></span></div>
                <div><span class="r-caption">IP:</span> <span class="detalle-ip"></span></div>
                <div><span class="r-caption">Acción:</span> <span class="detalle-accion"></span></div>
                <div><span class="r-caption">Modelo:</span> <span class="detalle-modelo"></span></div>
                <div><span class="r-caption">ID:</span> <span class="detalle-modelo-id"></span></div>
            </div>
            <div class="bloque-valor-anterior" style="display:none;">
                <h4 class="r-caption" style="margin-bottom:var(--space-2);">Valor anterior</h4>
                <table class="r-table" style="font-size:0.75rem;"><tbody class="tabla-valor-anterior"></tbody></table>
            </div>
            <div class="bloque-valor-nuevo" style="display:none;">
                <h4 class="r-caption" style="margin-bottom:var(--space-2);">Valor nuevo</h4>
                <table class="r-table" style="font-size:0.75rem;"><tbody class="tabla-valor-nuevo"></tbody></table>
            </div>
        </div>
        <div style="padding:var(--space-4) var(--space-6); border-top:1px solid var(--color-line); display:flex; justify-content:flex-end;">
            <button type="button" class="cerrar-modal r-btn r-btn-ghost r-btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal-detalle');
    function pintarTabla(tbody, jsonString) {
        tbody.innerHTML = '';
        const bloque = tbody.closest('[class*="bloque-"]');
        let datos = null;
        try { datos = JSON.parse(jsonString); } catch (e) { datos = null; }
        if (!datos || typeof datos !== 'object' || Object.keys(datos).length === 0) { bloque.style.display = 'none'; return; }
        bloque.style.display = 'block';
        Object.entries(datos).forEach(([clave, valor]) => {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td style="padding:8px 12px; font-weight:500; background:var(--color-paper); width:33%;">' + clave + '</td><td style="padding:8px 12px; word-break:break-all;">' + (valor === null || valor === undefined ? '—' : (typeof valor === 'object' ? JSON.stringify(valor) : String(valor))) + '</td>';
            tbody.appendChild(tr);
        });
    }
    document.querySelectorAll('.btn-ver-detalle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            modal.querySelector('.detalle-fecha').textContent = btn.dataset.fecha || '—';
            modal.querySelector('.detalle-usuario').textContent = btn.dataset.usuario || '—';
            modal.querySelector('.detalle-ip').textContent = btn.dataset.ip || '—';
            modal.querySelector('.detalle-accion').textContent = btn.dataset.accion || '—';
            modal.querySelector('.detalle-modelo').textContent = btn.dataset.modelo || '—';
            modal.querySelector('.detalle-modelo-id').textContent = btn.dataset.modeloId || '—';
            pintarTabla(modal.querySelector('.tabla-valor-anterior'), btn.dataset.anterior);
            pintarTabla(modal.querySelector('.tabla-valor-nuevo'), btn.dataset.nuevo);
            modal.style.display = 'flex';
        });
    });
    modal.querySelectorAll('.cerrar-modal').forEach(function (btn) { btn.addEventListener('click', function () { modal.style.display = 'none'; }); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
});
</script>
