<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1C2422">
    <meta name="color-scheme" content="light">
    <title>@yield('title', config('app.name', 'Sistema Administrativo'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Rhythm Design System (estilos locales) -->
    <link rel="stylesheet" href="{{ asset('css/rhythm.css') }}">
</head>
<body class="r-app-body" style="color: var(--color-ink); font-family: var(--font-body);">

    <a href="#contenido-principal" class="r-skip-link">Saltar al contenido</a>

    {{-- Sidebar --}}
    @include('layouts.partials.sidebar')
    <div class="r-sidebar-backdrop" id="sidebar-backdrop" onclick="closeSidebar()"></div>

    {{-- Main content area --}}
    <div class="r-content">

        {{-- Top bar --}}
        <div class="r-topbar">
            <div class="r-flex r-items-center r-gap-4">
                <button id="mobile-menu-btn" class="r-btn-ghost r-btn-sm" style="display:none;" onclick="toggleSidebar()">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <p class="r-display-m" style="margin:0; font-size: clamp(1rem, 2vw, 1.5rem);">
                    @yield('page_title', 'Panel')
                </p>
            </div>

            <div class="r-flex r-items-center r-gap-4">
                @php
                    $notificacionesPendientes = \App\Models\Notificacion::where('user_id', auth()->id())->where('leida', false)->count() ?? 0;
                @endphp

                <a href="{{ route('notificaciones.index') }}" class="relative" style="display:inline-flex;color:var(--color-ink-soft);" aria-label="Notificaciones{{ $notificacionesPendientes > 0 ? ' ('.$notificacionesPendientes.' sin leer)' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($notificacionesPendientes > 0)
                        <span style="position:absolute; top:-4px; right:-6px; min-width:16px; height:16px; padding:0 3px; background:var(--color-marigold); color:var(--color-ink); font-size:9px; font-weight:700; border-radius:99px; display:flex; align-items:center; justify-content:center; font-family:var(--font-mono);">
                            {{ $notificacionesPendientes > 9 ? '9+' : $notificacionesPendientes }}
                        </span>
                    @endif
                </a>

                <div style="width:1px; height:20px; background:var(--color-line);"></div>

                <span class="r-caption" style="font-size:0.75rem; text-transform:none; letter-spacing:0; color:var(--color-ink-soft);">
                    {{ Auth::user()->name }}
                </span>

                @if(Auth::user()->role)
                    <span class="r-tag" style="font-size:0.6rem; padding:3px 10px;">
                        {{ Auth::user()->role->nombre }}
                    </span>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="r-btn-ghost r-btn-sm" style="font-size:0.75rem; color:var(--color-ink-soft);">
                        Salir
                    </button>
                </form>
            </div>
        </div>

        {{-- Flash Messages (se muestran como toasts vía app.js) --}}
        @if(session('success'))
            <span data-flash="success" data-message="{{ session('success') }}" style="display:none;"></span>
        @endif

        @if(session('error'))
            <span data-flash="error" data-message="{{ session('error') }}" style="display:none;"></span>
        @endif

        {{-- Page Content --}}
        <main id="contenido-principal" class="r-page-body">
            @yield('content')
        </main>
    </div>

    <script>
    function toggleSidebar() {
        document.querySelector('.r-sidebar').classList.toggle('open');
        document.getElementById('sidebar-backdrop').classList.toggle('active');
    }
    function closeSidebar() {
        document.querySelector('.r-sidebar').classList.remove('open');
        document.getElementById('sidebar-backdrop').classList.remove('active');
    }
    </script>

    @yield('scripts')
</body>
</html>
