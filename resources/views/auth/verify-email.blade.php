@extends('layouts.guest')

@section('content')
    <p class="r-body" style="margin-bottom: var(--space-6);">
        ¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu correo electrónico haciendo clic en el enlace que te acabamos de enviar? Si no recibiste el correo, con gusto te enviaremos otro.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="r-flash r-flash-success">
            Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
        </div>
    @endif

    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: var(--space-6);">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="r-btn r-btn-accent">
                Reenviar Correo de Verificación
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="r-link">
                Cerrar Sesión
            </button>
        </form>
    </div>
@endsection
