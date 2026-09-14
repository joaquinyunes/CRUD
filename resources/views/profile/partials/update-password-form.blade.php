<section>
    <header style="margin-bottom:var(--space-6);">
        <h2 class="r-display-m" style="font-size:1.125rem;">{{ __('Cambiar contraseña') }}</h2>
        <p class="r-body" style="font-size:0.9rem;margin:4px 0 0;">
            {{ __('Usá una contraseña larga y única para mantener la cuenta segura.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="r-stack">
        @csrf
        @method('put')

        <div class="r-field" style="margin:0;">
            <x-input-label for="update_password_current_password" :value="__('Contraseña actual')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="r-field" style="margin:0;">
            <x-input-label for="update_password_password" :value="__('Contraseña nueva')" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="r-field" style="margin:0;">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="r-cluster">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2200)"
                   class="r-caption" style="text-transform:none;letter-spacing:0;">{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
