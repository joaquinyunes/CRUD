<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Sistema Administrativo'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

    <div class="flex h-full">

        @include('layouts.partials.sidebar')

        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <header class="flex items-center justify-between bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 h-16 shrink-0">
                <h1 class="text-lg font-semibold truncate">@yield('page_title', 'Dashboard')</h1>

                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500 dark:text-gray-400 hidden sm:inline">
                        {{ Auth::user()->name }}
                        @if(Auth::user()->role)
                            <span class="ml-1 text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full">
                                {{ Auth::user()->role->nombre }}
                            </span>
                        @endif
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="text-sm text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            Salir
                        </button>
                    </form>
                </div>
            </header>

            @if(session('success'))
                <div class="mx-6 mt-4 p-3 bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-4 p-3 bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>
