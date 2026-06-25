<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Gestión de Roles') }}
            </h2>
            <a href="{{ route('roles.usuarios') }}"
               class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                Ver Usuarios y Roles
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-100 p-4 text-sm text-green-800 dark:bg-green-900 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-100 p-4 text-sm text-red-800 dark:bg-red-900 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-3 pr-6 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rol</th>
                                <th class="pb-3 pr-6 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Permisos asignados</th>
                                <th class="pb-3 pr-6 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Usuarios con este rol</th>
                                <th class="pb-3 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($roles as $role)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="py-3 pr-6 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $role->nombre }}
                                        @if ($role->nombre === \App\Models\Role::ADMINISTRADOR)
                                            <span class="ml-2 rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                Acceso total
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-6">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $role->nombre === \App\Models\Role::ADMINISTRADOR ? 'Todos' : $role->permissions_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-6">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $role->users_count }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        @if ($role->nombre !== \App\Models\Role::ADMINISTRADOR)
                                            <a href="{{ route('roles.edit', $role) }}"
                                               class="inline-flex items-center text-indigo-600 hover:underline dark:text-indigo-400">
                                                Editar permisos
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">No editable</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
