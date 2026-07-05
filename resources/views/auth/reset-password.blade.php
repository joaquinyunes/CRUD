@extends('layouts.guest')

@section('content')
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label class="r-label" for="email">Email</label>
        <input id="email" class="r-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
        @error('email')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <label class="r-label" for="password" style="margin-top: var(--space-4);">Nueva Contraseña</label>
        <input id="password" class="r-input" type="password" name="password" required autocomplete="new-password">
        @error('password')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <label class="r-label" for="password_confirmation" style="margin-top: var(--space-4);">Confirmar Contraseña</label>
        <input id="password_confirmation" class="r-input" type="password" name="password_confirmation" required autocomplete="new-password">
        @error('password_confirmation')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <button type="submit" class="r-btn r-btn-accent" style="margin-top: var(--space-6);">
            Restablecer Contraseña
        </button>
    </form>
@endsection
