<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Sistema Administrativo'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Rhythm Design System -->
    <link rel="stylesheet" href="{{ asset('css/rhythm.css') }}">

    <!-- GSAP (loaded early for init) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

    <!-- Lenis Smooth Scroll -->
    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>

    <style>
        body { opacity: 0; }
        html.lenis, html.lenis body { height: auto; }
        .lenis.lenis-smooth { scroll-behavior: auto !important; }
    </style>
</head>
<body style="background: var(--color-paper); color: var(--color-ink); font-family: var(--font-body);">

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
                <h1 class="r-display-m" style="margin:0; font-size: clamp(1rem, 2vw, 1.5rem);">
                    @yield('page_title', 'Dashboard')
                </h1>
            </div>

            <div class="r-flex r-items-center r-gap-4">
                @php
                    $notificacionesPendientes = \App\Models\Notificacion::where('user_id', auth()->id())->where('leida', false)->count() ?? 0;
                @endphp

                <div class="relative" style="cursor:pointer;" data-tooltip="Notificaciones">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-ink-soft);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($notificacionesPendientes > 0)
                        <span style="position:absolute; top:-4px; right:-6px; width:16px; height:16px; background:var(--color-marigold); color:var(--color-ink); font-size:9px; font-weight:700; border-radius:99px; display:flex; align-items:center; justify-content:center; font-family:var(--font-mono);">
                            {{ $notificacionesPendientes > 9 ? '9+' : $notificacionesPendientes }}
                        </span>
                    @endif
                </div>

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

        {{-- Flash Messages --}}
        @if(session('success'))
            <div style="padding: 0 var(--space-8);">
                <div class="r-flash-success r-mt-4" data-reveal="fade-up">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span style="font-weight:500;">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div style="padding: 0 var(--space-8);">
                <div class="r-flash-error r-mt-4" data-reveal="fade-up">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span style="font-weight:500;">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- Page Content --}}
        <div class="r-page-body">
            @yield('content')
        </div>
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

    document.addEventListener('DOMContentLoaded', function() {

        // Page entrance
        gsap.to('body', { opacity: 1, duration: 0.4, ease: 'power2.out' });

        // Lenis smooth scroll
        const lenis = new Lenis({
            duration: 1.0,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            orientation: 'vertical',
            smoothWheel: true,
        });

        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);

        lenis.on('scroll', ScrollTrigger.update);
        gsap.ticker.add((time) => { lenis.raf(time * 1000); });
        gsap.ticker.lagSmoothing(0);

        // Scroll reveal for [data-reveal] elements
        document.querySelectorAll('[data-reveal]').forEach((el, i) => {
            ScrollTrigger.create({
                trigger: el,
                start: 'top 90%',
                once: true,
                onEnter: () => {
                    const type = el.getAttribute('data-reveal') || 'fade-up';
                    const delay = parseFloat(el.dataset.revealDelay || 0);

                    let from = { opacity: 0, y: 24 };
                    if (type === 'fade-left') from = { opacity: 0, x: -24 };
                    if (type === 'fade-right') from = { opacity: 0, x: 24 };
                    if (type === 'scale') from = { opacity: 0, scale: 0.95 };

                    gsap.fromTo(el, from, {
                        opacity: 1, x: 0, y: 0, scale: 1,
                        duration: 0.6,
                        delay: delay,
                        ease: 'power3.out',
                        onComplete: () => el.classList.add('revealed')
                    });
                }
            });
        });

        // Rhythm divider animation
        document.querySelectorAll('.r-divider').forEach(el => {
            ScrollTrigger.create({
                trigger: el,
                start: 'top 85%',
                once: true,
                onEnter: () => el.classList.add('visible')
            });
        });

    });
    </script>

    @yield('scripts')
</body>
</html>
