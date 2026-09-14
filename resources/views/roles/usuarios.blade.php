@extends('layouts.app')

@section('page_title', 'Usuarios y roles')

@section('content')
<div style="max-width: 64rem; margin: 0 auto;">

    <div class="r-head">
        <h2 class="r-display-m">Usuarios y roles</h2>
        <a href="{{ route('roles.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver a roles</a>
    </div>

    @if (session('success'))
        <div class="r-flash-success r-mb-6">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="r-flash-error r-mb-6">{{ session('error') }}</div>
    @endif

    <div class="r-card-flat" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="r-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol actual</th>
                        <th>Asignar rol</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td style="font-weight:500;">
                                {{ $usuario->name }}
                                @if ($usuario->id === auth()->id())
                                    <span class="r-caption" style="text-transform:none;letter-spacing:0;margin-left:4px;">(vos)</span>
                                @endif
                            </td>
                            <td class="r-mono" style="font-size:0.8125rem;color:var(--color-ink-soft);">{{ $usuario->email }}</td>
                            <td>
                                @if ($usuario->role)
                                    <span class="r-tag r-tag-marigold">{{ $usuario->role->nombre }}</span>
                                @else
                                    <span class="r-tag r-tag-danger">Sin rol</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('roles.asignar-rol', $usuario) }}" method="POST" class="r-cluster" style="gap:var(--space-2);">
                                    @csrf
                                    @method('PUT')
                                    <select name="role_id" class="r-select" style="min-height:34px;padding:4px 32px 4px 10px;font-size:0.8125rem;width:auto;">
                                        <option value="">— Sin rol —</option>
                                        @foreach ($roles as $rol)
                                            <option value="{{ $rol->id }}" {{ $usuario->role_id == $rol->id ? 'selected' : '' }}>
                                                {{ $rol->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="r-btn r-btn-primary r-btn-sm">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
