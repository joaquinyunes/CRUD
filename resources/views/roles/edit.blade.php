<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Editar permisos — <span class="text-indigo-600 dark:text-indigo-400">{{ $role->nombre }}</span>
            </h2>
            <a href="{{ route('roles.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                ← Volver a roles
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">

            <form action="{{ route('roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-100 p-4 text-sm text-red-800 dark:bg-red-900 dark:text-red-200">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">

                    @foreach ($todosLosPermisos as $modulo => $permisos)
                        <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                        {{ ucfirst($modulo) }}
                                    </h3>
                                    <label class="flex cursor-pointer items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <input type="checkbox"
                                               class="toggle-modulo rounded border-gray-300"
                                               data-modulo="{{ $modulo }}"
                                               {{ $permisos->every(fn($p) => in_array($p->clave, $permisosAsignados)) ? 'checked' : '' }}>
                                        Seleccionar todos
                                    </label>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 p-6 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($permisos as $permiso)
                                    @php
                                        $accion = explode('.', $permiso->clave)[1] ?? $permiso->clave;
                                    @endphp
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                                        <input type="checkbox"
                                               name="permisos[]"
                                               value="{{ $permiso->clave }}"
                                               class="permiso-{{ $modulo }} rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                               {{ in_array($permiso->clave, $permisosAsignados) ? 'checked' : '' }}>
                                        <span>{{ ucfirst($accion) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('roles.index') }}"
                           class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            Guardar permisos
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

    @push('scripts')
    <script>
        document.querySelectorAll('.toggle-modulo').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                const modulo = this.dataset.modulo;
                document.querySelectorAll('.permiso-' + modulo).forEach(function (checkbox) {
                    checkbox.checked = toggle.checked;
                });
            });
        });

        document.querySelectorAll('[data-modulo]').forEach(function (toggle) {
            const modulo = toggle.dataset.modulo;
            const checkboxes = document.querySelectorAll('.permiso-' + modulo);
            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    const allChecked = Array.from(checkboxes).every(function (cb) { return cb.checked; });
                    toggle.checked = allChecked;
                });
            });
        });
    </script>
    @endpush
</x-app-layout>
