<section>
    <header class="r-card-title" style="display:block;">
        <h2 class="r-display-m" style="font-size:1.125rem;">{{ __('Datos del perfil') }}</h2>
        <p class="r-body" style="font-size:0.9rem;margin:4px 0 0;">
            {{ __('Actualizá el nombre y el correo de tu cuenta.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="r-stack r-mt-6">
        @csrf
        @method('patch')

        <div class="r-field" style="margin:0;">
            <x-input-label for="name" :value="__('Nombre')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="r-field" style="margin:0;">
            <x-input-label for="email" :value="__('Correo')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="r-mt-2">
                    <p class="r-body" style="font-size:0.875rem;">
                        {{ __('Tu correo no está verificado.') }}
                        <button form="send-verification" class="r-btn r-btn-ghost r-btn-sm" style="margin-left:6px;">
                            {{ __('Reenviar verificación') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="r-mt-2" style="font-size:0.875rem;color:var(--color-success);font-weight:500;">
                            {{ __('Te enviamos un nuevo enlace de verificación.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="r-cluster">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2200)"
                   class="r-caption" style="text-transform:none;letter-spacing:0;">{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
