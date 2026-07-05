<aside class="r-sidebar">

    {{-- Logo --}}
    <div class="r-sidebar-logo">
        <div class="r-sidebar-logo-inner">
            <div class="r-sidebar-logo-icon">SA</div>
            <span class="r-sidebar-logo-text">{{ config('app.name', 'SistemaAdmin') }}</span>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="r-sidebar-nav">

        <a href="{{ route('dashboard') }}" class="r-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Dashboard</span>
        </a>

        <div class="r-sidebar-section">Catálogo</div>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('productos.ver'))
        <a href="{{ route('productos.index') }}" class="r-sidebar-link {{ request()->routeIs('productos.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
            <span>Productos</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('categorias.ver'))
        <a href="{{ route('categorias.index') }}" class="r-sidebar-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
            <span>Categorías</span>
        </a>
        @endif

        <div class="r-sidebar-section">Clientes</div>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('clientes.ver'))
        <a href="{{ route('clientes.index') }}" class="r-sidebar-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
            <span>Clientes</span>
        </a>
        @endif

        <div class="r-sidebar-section">Ventas</div>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('ventas.ver'))
        <a href="{{ route('ventas.index') }}" class="r-sidebar-link {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Ventas</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('compras.ver'))
        <a href="{{ route('compras.index') }}" class="r-sidebar-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span>Compras</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('proveedores.ver'))
        <a href="{{ route('proveedores.index') }}" class="r-sidebar-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <span>Proveedores</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('stock.ver'))
        <a href="{{ route('stock.index') }}" class="r-sidebar-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
            <span>Stock</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('reportes.ver'))
        <a href="{{ route('reportes.index') }}" class="r-sidebar-link {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Reportes</span>
        </a>
        @endif

        <a href="{{ route('archivos.index') }}" class="r-sidebar-link {{ request()->routeIs('archivos.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <span>Archivos</span>
        </a>

        <a href="{{ route('tareas.index') }}" class="r-sidebar-link {{ request()->routeIs('tareas.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Tareas</span>
        </a>

        <a href="{{ route('calendario.index') }}" class="r-sidebar-link {{ request()->routeIs('calendario.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Calendario</span>
        </a>

        <div class="r-sidebar-section">Administración</div>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('roles.ver'))
        <a href="{{ route('roles.index') }}" class="r-sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Roles y Permisos</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('usuarios.ver'))
        <a href="{{ route('roles.usuarios') }}" class="r-sidebar-link {{ request()->routeIs('roles.usuarios') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
            <span>Usuarios</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('auditoria.ver'))
        <a href="{{ route('auditoria.index') }}" class="r-sidebar-link {{ request()->routeIs('auditoria.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Auditoría</span>
        </a>
        @endif

        <a href="{{ route('configuracion.index') }}" class="r-sidebar-link {{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Configuración</span>
        </a>

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('configuracion.ver'))
        <a href="{{ route('unidades-medida.index') }}" class="r-sidebar-link {{ request()->routeIs('unidades-medida.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span>Unidades de Medida</span>
        </a>
        @endif

        @if(Auth::user()->role && Auth::user()->role->tienePermiso('configuracion.ver'))
        <a href="{{ route('metodos-pago.index') }}" class="r-sidebar-link {{ request()->routeIs('metodos-pago.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span>Métodos de Pago</span>
        </a>
        @endif

        <a href="{{ route('importar.index') }}" class="r-sidebar-link {{ request()->routeIs('importar.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Importar CSV</span>
        </a>

        <a href="{{ route('backup.index') }}" class="r-sidebar-link {{ request()->routeIs('backup.*') ? 'active' : '' }}">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
            <span>Backup / Restore</span>
        </a>

    </nav>

    {{-- Footer --}}
    <div class="r-sidebar-footer">
        <span class="r-caption" style="font-size:0.5625rem; color:rgba(239,231,218,0.3); text-transform:none; letter-spacing:0;">
            v1.0 · Rhythm
        </span>
    </div>

</aside>
