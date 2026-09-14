@extends('layouts.app')

@section('page_title', 'Editar permisos — ' . $role->nombre)

@section('content')
<div style="max-width: 64rem; margin: 0 auto;">

    <div class="r-head">
        <h2 class="r-display-m">
            Permisos de <span style="color:var(--color-marigold-deep);">{{ $role->nombre }}</span>
        </h2>
        <a href="{{ route('roles.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver a roles</a>
    </div>

    <form action="{{ route('roles.update', $role) }}" method="POST" class="r-stack-lg">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="r-flash-error">
                <ul style="list-style:disc;margin:0;padding-left:1.2em;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @foreach ($todosLosPermisos as $modulo => $permisos)
            <div class="r-card-flat">
                <div class="r-card-title">
                    <h3 class="r-label" style="margin:0;">{{ ucfirst($modulo) }}</h3>
                    <label class="r-cluster" style="gap:var(--space-2);cursor:pointer;font-size:0.75rem;color:var(--color-ink-soft);">
                        <input type="checkbox" class="toggle-modulo" data-modulo="{{ $modulo }}"
                               style="width:16px;height:16px;accent-color:var(--color-marigold);"
                               {{ $permisos->every(fn($p) => in_array($p->clave, $permisosAsignados)) ? 'checked' : '' }}>
                        Seleccionar todos
                    </label>
                </div>

                <div class="r-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
                    @foreach ($permisos as $permiso)
                        @php $accion = explode('.', $permiso->clave)[1] ?? $permiso->clave; @endphp
                        <label class="r-cluster" style="gap:var(--space-2);cursor:pointer;font-size:0.9rem;color:var(--color-ink);">
                            <input type="checkbox" name="permisos[]" value="{{ $permiso->clave }}"
                                   class="permiso-{{ $modulo }}"
                                   style="width:16px;height:16px;accent-color:var(--color-marigold);"
                                   {{ in_array($permiso->clave, $permisosAsignados) ? 'checked' : '' }}>
                            <span>{{ ucfirst($accion) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="r-cluster r-justify-end">
            <a href="{{ route('roles.index') }}" class="r-btn r-btn-ghost">Cancelar</a>
            <button type="submit" class="r-btn r-btn-primary">Guardar permisos</button>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.toggle-modulo').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            document.querySelectorAll('.permiso-' + this.dataset.modulo).forEach(function (cb) {
                cb.checked = toggle.checked;
            });
        });
    });

    document.querySelectorAll('[data-modulo]').forEach(function (toggle) {
        const checkboxes = document.querySelectorAll('.permiso-' + toggle.dataset.modulo);
        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                toggle.checked = Array.from(checkboxes).every(function (cb) { return cb.checked; });
            });
        });
    });
</script>
@endsection
