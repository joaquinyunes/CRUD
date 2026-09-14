<section class="r-stack">
    <header>
        <h2 class="r-display-m" style="font-size:1.125rem;">{{ __('Eliminar cuenta') }}</h2>
        <p class="r-body" style="font-size:0.9rem;margin:4px 0 0;">
            {{ __('Al eliminar tu cuenta se borran de forma permanente todos sus datos. Descargá antes lo que quieras conservar.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        style="align-self:flex-start;"
    >{{ __('Eliminar cuenta') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" style="padding:var(--space-8);">
            @csrf
            @method('delete')

            <h2 class="r-display-m" style="font-size:1.125rem;">
                {{ __('¿Seguro que querés eliminar tu cuenta?') }}
            </h2>

            <p class="r-body" style="font-size:0.9rem;margin:var(--space-2) 0 var(--space-6);">
                {{ __('Esta acción es permanente. Ingresá tu contraseña para confirmar.') }}
            </p>

            <div class="r-field">
                <x-input-label for="password" value="{{ __('Contraseña') }}" class="sr-only" />
                <x-text-input id="password" name="password" type="password" placeholder="{{ __('Contraseña') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="r-cluster" style="justify-content:flex-end;">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Eliminar cuenta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
