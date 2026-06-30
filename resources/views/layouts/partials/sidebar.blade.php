<aside class="flex flex-col w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shrink-0">

    <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-sm">
            SA
        </div>
        <span class="font-semibold text-sm truncate">
            {{ config('app.name', 'SistemaAdmin') }}
        </span>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">

        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>

        <p class="sidebar-section-label">Catálogo</p>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('productos.ver'))
        <a href="{{ route('productos.index') }}"
           class="sidebar-link {{ request()->routeIs('productos.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
            </svg>
            <span>Productos</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('categorias.ver'))
        <a href="{{ route('categorias.index') }}"
           class="sidebar-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span>Categorías</span>
        </a>
        @endif

        <p class="sidebar-section-label">Clientes</p>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('clientes.ver'))
        <a href="{{ route('clientes.index') }}"
           class="sidebar-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            <span>Clientes</span>
        </a>
        @endif

        <p class="sidebar-section-label">Ventas</p>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('ventas.ver'))
        <a href="{{ route('ventas.index') }}"
           class="sidebar-link {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <span>Ventas</span>
        </a>
        @endif

        <p class="sidebar-section-label">Administración</p>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('roles.ver'))
        <a href="{{ route('roles.index') }}"
           class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <span>Roles y Permisos</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('usuarios.ver'))
        <a href="{{ route('roles.usuarios') }}"
           class="sidebar-link {{ request()->routeIs('roles.usuarios') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
            </svg>
            <span>Usuarios</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('auditoria.ver'))
        <a href="{{ route('auditoria.index') }}"
           class="sidebar-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span>Auditoría</span>
        </a>
        @endif

    </nav>

    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-xs text-gray-400 dark:text-gray-500">
        v1.0 · Nivel 1
    </div>
</aside>

<style>
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: #6b7280;
        text-decoration: none;
        transition: background-color 0.15s, color 0.15s;
    }
    .sidebar-link:hover {
        background-color: #f3f4f6;
        color: #111827;
    }
    .dark .sidebar-link:hover {
        background-color: #374151;
        color: #f9fafb;
    }
    .sidebar-link.active {
        background-color: #eef2ff;
        color: #4f46e5;
        font-weight: 500;
    }
    .dark .sidebar-link.active {
        background-color: #312e81;
        color: #a5b4fc;
    }
    .sidebar-section-label {
        padding: 0.75rem 0.75rem 0.25rem;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #9ca3af;
    }
</style>
