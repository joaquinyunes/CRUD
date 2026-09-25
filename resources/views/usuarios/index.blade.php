@extends('layouts.app')

@section('page_title', 'Usuarios')

@section('content')

@php
    $puedeCrear  = auth()->user()->role?->tienePermiso('usuarios.crear');
    $puedeEditar = auth()->user()->role?->tienePermiso('usuarios.editar');
    $puedeBorrar = auth()->user()->role?->tienePermiso('usuarios.eliminar');
@endphp

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Usuarios</h2>
    <div class="r-flex r-gap-3">
        <a href="{{ route('roles.index') }}" class="r-btn r-btn-ghost r-btn-sm">Roles y permisos</a>
        @if($puedeCrear)
            <a href="{{ route('usuarios.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo usuario</a>
        @endif
    </div>
</div>

<div class="r-card-flat r-mb-6" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="GET" action="{{ route('usuarios.index') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:220px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o correo…" class="r-input">
        </div>
        <div>
            <label class="r-label">Rol</label>
            <select name="role_id" class="r-select">
                <option value="">Todos</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->id }}" {{ request('role_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        @if(request()->hasAny(['buscar', 'role_id']))
            <a href="{{ route('usuarios.index') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
        @endif
    </form>
</div>

<div class="r-card-flat" data-reveal="fade-up" data-reveal-delay="0.2">
    <div style="overflow-x: auto;">
        <table class="r-table">
            <thead>
                <tr>
                    <x-th campo="nombre">Usuario</x-th>
                    <x-th campo="email">Correo</x-th>
                    <x-th campo="rol">Rol</x-th>
                    <x-th campo="creado" inicial="desc">Alta</x-th>
                    @if($puedeEditar)
                        <th style="width:260px;">Cambiar rol</th>
                    @endif
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                    <tr>
                        <td style="font-weight:500;">
                            {{ $usuario->name }}
                            @if($usuario->id === auth()->id())
                                <span class="r-tag" style="margin-left:6px;">vos</span>
                            @endif
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $usuario->email }}</td>
                        <td>
                            @if($usuario->role)
                                <span class="r-tag r-tag-marigold">{{ $usuario->role->nombre }}</span>
                            @else
                                <span class="r-tag r-tag-danger">Sin rol</span>
                            @endif
                        </td>
                        <td style="color:var(--color-ink-soft);">{{ $usuario->created_at?->format('d/m/Y') ?? '—' }}</td>

                        @if($puedeEditar)
                            <td>
                                <form method="POST" action="{{ route('usuarios.asignar-rol', $usuario) }}" class="r-flex r-gap-2 r-items-center">
                                    @csrf @method('PUT')
                                    <select name="role_id" class="r-select" style="min-width:150px;">
                                        <option value="">— Sin rol —</option>
                                        @foreach($roles as $rol)
                                            <option value="{{ $rol->id }}" {{ $usuario->role_id == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Guardar</button>
                                </form>
                            </td>
                        @endif

                        <td style="text-align:right;">
                            <div class="r-flex r-gap-3" style="justify-content:flex-end;">
                                @if($puedeEditar)
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px;">Editar</a>
                                @endif
                                @if($puedeBorrar && $usuario->id !== auth()->id())
                                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" style="display:inline;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem; padding:4px 12px; color:#dc2626;"
                                                onclick="return confirm('¿Eliminar al usuario {{ $usuario->name }}?')">Eliminar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $puedeEditar ? 6 : 5 }}" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">No se encontraron usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($usuarios->hasPages())
    <div class="r-flex r-items-center r-justify-between r-mt-6" style="color:var(--color-ink-soft); font-size:0.875rem;">
        <span>Mostrando {{ $usuarios->firstItem() }}–{{ $usuarios->lastItem() }} de {{ $usuarios->total() }} usuarios</span>
        {{ $usuarios->links() }}
    </div>
@endif

@endsection
