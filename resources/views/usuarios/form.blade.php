@extends('layouts.app')

@section('page_title', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')

<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">{{ $usuario->exists ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
    <a href="{{ route('usuarios.index') }}" class="r-btn r-btn-ghost r-btn-sm">&larr; Volver</a>
</div>

@if ($errors->any())
    <div class="r-flash-error r-mb-6" data-reveal="fade-up">
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="r-card-flat" style="max-width:640px;" data-reveal="fade-up" data-reveal-delay="0.1">
    <form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
          class="r-flex-col r-gap-4" style="display:flex;">
        @csrf
        @if($usuario->exists) @method('PUT') @endif

        <div>
            <label class="r-label">Nombre *</label>
            <input type="text" name="name" required value="{{ old('name', $usuario->name) }}" class="r-input" autofocus>
        </div>

        <div>
            <label class="r-label">Correo *</label>
            <input type="email" name="email" required value="{{ old('email', $usuario->email) }}" class="r-input">
        </div>

        <div>
            <label class="r-label">Rol</label>
            <select name="role_id" class="r-select r-w-full">
                <option value="">— Sin rol (no puede operar) —</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->id }}" {{ old('role_id', $usuario->role_id) == $rol->id ? 'selected' : '' }}>
                        {{ $rol->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="r-label">
                {{ $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña *' }}
            </label>
            <input type="password" name="password" {{ $usuario->exists ? '' : 'required' }}
                   autocomplete="new-password" class="r-input" placeholder="Mínimo 8 caracteres">
        </div>

        <div>
            <label class="r-label">Repetir contraseña</label>
            <input type="password" name="password_confirmation" {{ $usuario->exists ? '' : 'required' }}
                   autocomplete="new-password" class="r-input">
        </div>

        <div class="r-flex r-gap-3 r-mt-2" style="justify-content:flex-end;">
            <a href="{{ route('usuarios.index') }}" class="r-btn r-btn-ghost r-btn-sm">Cancelar</a>
            <button type="submit" class="r-btn r-btn-primary r-btn-sm">
                {{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}
            </button>
        </div>
    </form>
</div>

@endsection
