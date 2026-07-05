@extends('layouts.guest')

@section('content')
    <p class="r-body" style="margin-bottom: var(--space-6);">
        Esta es una área segura de la aplicación. Por favor confirma tu contraseña antes de continuar.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <label class="r-label" for="password">Contraseña</label>
        <input id="password" class="r-input" type="password" name="password" required autocomplete="current-password">
        @error('password')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <button type="submit" class="r-btn r-btn-accent" style="margin-top: var(--space-6);">
            Confirmar
        </button>
    </form>
@endsection
