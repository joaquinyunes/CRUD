@extends('layouts.guest')

@section('content')
    <p class="r-body" style="margin-bottom: var(--space-6);">
        ¿Olvidaste tu contraseña? No hay problema. Solo indícanos tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
    </p>

    @if (session('status'))
        <div class="r-flash r-flash-success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label class="r-label" for="email">Email</label>
        <input id="email" class="r-input" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@admin.com">
        @error('email')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <button type="submit" class="r-btn r-btn-accent" style="margin-top: var(--space-6);">
            Enviar Enlace de Restablecimiento
        </button>
    </form>
@endsection
