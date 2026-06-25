<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Usuarios y Roles
            </h2>
            <a href="{{ route('roles.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                ← Volver a roles
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">

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
                                <th class="pb-3 pr-6 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Usuario</th>
                                <th class="pb-3 pr-6 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</th>
                                <th class="pb-3 pr-6 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Rol actual</th>
                                <th class="pb-3 font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Asignar rol</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($usuarios as $usuario)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="py-3 pr-6 font-medium text-gray-900 dark:text-gray-100">
                                        {{ $usuario->name }}
                                        @if ($usuario->id === auth()->id())
                                            <span class="ml-1 text-xs text-gray-400">(vos)</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-6 text-gray-500 dark:text-gray-400">
                                        {{ $usuario->email }}
                                    </td>
                                    <td class="py-3 pr-6">
                                        @if ($usuario->role)
                                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                                {{ $usuario->role->nombre }}
                                            </span>
                                        @else
                                            <span class="text-xs text-red-500">Sin rol</span>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <form action="{{ route('roles.asignar-rol', $usuario) }}" method="POST"
                                              class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="role_id"
                                                    class="rounded-md border-gray-300 py-1 pl-2 pr-8 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
                                                <option value="">— Sin rol —</option>
                                                @foreach ($roles as $rol)
                                                    <option value="{{ $rol->id }}"
                                                            {{ $usuario->role_id == $rol->id ? 'selected' : '' }}>
                                                        {{ $rol->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                    class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                Guardar
                                            </button>
                                        </form>
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
