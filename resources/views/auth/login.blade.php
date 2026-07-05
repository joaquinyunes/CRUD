@extends('layouts.guest')

@section('content')
    @if (session('status'))
        <div class="r-flash r-flash-success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label class="r-label" for="email">Email</label>
        <input id="email" class="r-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="admin@admin.com">
        @error('email')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <label class="r-label" for="password">Password</label>
        <input id="password" class="r-input" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        @error('password')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <div class="r-flex-between">
            <div class="r-checkbox-wrap">
                <input id="remember_me" type="checkbox" name="remember">
                <label for="remember_me">Recordarme</label>
            </div>

            @if (Route::has('password.request'))
                <a class="r-link" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <button type="submit" class="r-btn r-btn-accent">
            Iniciar Sesión
        </button>
    </form>
@endsection
