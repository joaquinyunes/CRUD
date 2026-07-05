@extends('layouts.app')

@section('page_title', 'Gestión de Roles')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Gestión de Roles</h2>
    <a href="{{ route('roles.usuarios') }}" class="r-btn r-btn-primary r-btn-sm">Ver Usuarios y Roles</a>
</div>

@if (session('success'))
    <div class="r-flash-success r-mb-6" data-reveal="fade-up">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="r-flash-error r-mb-6" data-reveal="fade-up">{{ session('error') }}</div>
@endif

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.1">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Permisos asignados</th>
                    <th>Usuarios con este rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td style="font-weight:600;">
                            {{ $role->nombre }}
                            @if ($role->nombre === \App\Models\Role::ADMINISTRADOR)
                                <span class="r-tag r-tag-marigold" style="margin-left:8px;">Acceso total</span>
                            @endif
                        </td>
                        <td>
                            <span class="r-tag">{{ $role->nombre === \App\Models\Role::ADMINISTRADOR ? 'Todos' : $role->permissions_count }}</span>
                        </td>
                        <td>
                            <span class="r-tag">{{ $role->users_count }}</span>
                        </td>
                        <td>
                            @if ($role->nombre !== \App\Models\Role::ADMINISTRADOR)
                                <a href="{{ route('roles.edit', $role) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar permisos</a>
                            @else
                                <span class="r-mono" style="font-size:0.6875rem; color:var(--color-ink-soft);">No editable</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
