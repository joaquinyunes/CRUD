@extends('layouts.guest')

@section('content')
    <p class="r-caption" style="margin-bottom: var(--space-4);">
        Tu cuenta tiene doble factor. Abrí tu aplicación de autenticación y escribí el código de seis
        dígitos, o usá uno de recuperación si no tenés el teléfono a mano.
    </p>

    <form method="POST" action="{{ route('doble-factor.verificar') }}">
        @csrf

        <label class="r-label" for="codigo">Código</label>
        <input id="codigo"
               class="r-input r-mono"
               type="text"
               name="codigo"
               required
               autofocus
               autocomplete="one-time-code"
               inputmode="numeric"
               placeholder="123456">
        @error('codigo')
            <p class="r-error">{{ $message }}</p>
        @enderror

        <button type="submit" class="r-btn r-btn-accent">
            Verificar
        </button>
    </form>

    <form method="POST" action="{{ route('doble-factor.cancelar') }}" style="margin-top: var(--space-4);">
        @csrf
        @method('DELETE')
        <button type="submit" class="r-link" style="background:none; border:none; cursor:pointer; padding:0;">
            Volver al inicio de sesión
        </button>
    </form>
@endsection
