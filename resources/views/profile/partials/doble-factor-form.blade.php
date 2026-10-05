@php
    $usuario = auth()->user();
    $enAlta = $usuario->two_factor_secret !== null && ! $usuario->tieneDobleFactor();
    $dobleFactor = app(\App\Services\DobleFactorService::class);
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Doble factor de autenticación
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Un segundo código, generado por una aplicación en tu teléfono, además de la contraseña.
            Sirve cualquiera que use el estándar TOTP: Google Authenticator, Authy, 1Password,
            Bitwarden. Si alguien averigua tu contraseña, igual no entra.
        </p>
    </header>

    @if (session('success'))
        <p class="mt-4 text-sm text-green-700 dark:text-green-400">{{ session('success') }}</p>
    @endif

    {{-- Los códigos se muestran una sola vez, en el momento de generarlos. --}}
    @if (session('codigos_recuperacion'))
        <div class="mt-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-300">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                Códigos de recuperación
            </p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Guardalos en un lugar seguro. Cada uno sirve una sola vez y son la única manera de
                entrar si perdés el teléfono. No se vuelven a mostrar.
            </p>
            <ul class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm text-gray-900 dark:text-gray-100">
                @foreach (session('codigos_recuperacion') as $codigo)
                    <li>{{ $codigo }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($usuario->tieneDobleFactor())

        <p class="mt-4 text-sm text-gray-900 dark:text-gray-100">
            Activo desde el {{ $usuario->two_factor_confirmed_at->format('d/m/Y') }}.
            Te quedan {{ count($dobleFactor->codigosDeRecuperacion($usuario)) }} códigos de recuperación.
        </p>

        <div class="mt-4 flex items-center gap-4">
            <form method="POST" action="{{ route('doble-factor.codigos') }}">
                @csrf
                <x-secondary-button type="submit">Generar códigos nuevos</x-secondary-button>
            </form>
        </div>

        <form method="POST" action="{{ route('doble-factor.desactivar') }}" class="mt-6">
            @csrf
            @method('DELETE')

            <x-input-label for="password_df" value="Contraseña" />
            <x-text-input id="password_df" name="password" type="password" class="mt-1 block w-full md:w-3/4" />
            <x-input-error :messages="$errors->dobleFactor->get('password')" class="mt-2" />

            <x-danger-button class="mt-4">Desactivar doble factor</x-danger-button>
        </form>

    @elseif ($enAlta)

        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            Escaneá este código con tu aplicación y después escribí el código de seis dígitos que
            te muestre, para confirmar que quedó bien configurada.
        </p>

        <div class="mt-4 inline-block p-3 bg-white rounded-lg">
            {!! $dobleFactor->qrSvg($usuario) !!}
        </div>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            ¿No podés escanear? Cargá esta clave a mano:
            <span class="font-mono">{{ $dobleFactor->secretoDe($usuario) }}</span>
        </p>

        <form method="POST" action="{{ route('doble-factor.confirmar') }}" class="mt-4">
            @csrf

            <x-input-label for="codigo" value="Código de seis dígitos" />
            <x-text-input id="codigo" name="codigo" type="text" inputmode="numeric"
                          autocomplete="one-time-code" class="mt-1 block w-full md:w-1/2" />
            <x-input-error :messages="$errors->get('codigo')" class="mt-2" />

            <x-primary-button class="mt-4">Confirmar y activar</x-primary-button>
        </form>

    @else

        <form method="POST" action="{{ route('doble-factor.iniciar') }}" class="mt-4">
            @csrf
            <x-primary-button>Activar doble factor</x-primary-button>
        </form>

    @endif
</section>
