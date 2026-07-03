@php
    $noLeidas = \App\Models\Notificacion::paraUsuario(auth()->id())->noLeidas()->count();
@endphp

<div class="relative" x-data="{ open: false }">
    <button @click="open = !open"
            class="relative p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if($noLeidas > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white transform bg-red-500 rounded-full"
                  x-text="{{ $noLeidas }}">
                {{ $noLeidas }}
            </span>
        @endif
    </button>

    <div x-show="open" @click.away="open = false"
         x-transition
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden z-50"
         style="display: none;">

        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Notificaciones</h3>
            @if($noLeidas > 0)
                <form method="PATCH" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs text-indigo-600 hover:underline">Marcar todas leídas</button>
                </form>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto">
            @php
                $ultimas = \App\Models\Notificacion::paraUsuario(auth()->id())
                                                  ->orderBy('created_at', 'desc')
                                                  ->limit(10)
                                                  ->get();
            @endphp

            @forelse($ultimas as $notif)
                <a href="{{ $notif->url ? route('notificaciones.marcar-leida', $notif) : '#' }}"
                   class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $notif->leida ? '' : 'bg-indigo-50 dark:bg-indigo-900/20' }}">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 mt-0.5">
                            @if($notif->tipo === 'venta')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/50">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                            @elseif($notif->tipo === 'compra')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </span>
                            @elseif($notif->tipo === 'stock')
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 {{ $notif->leida ? '' : 'font-bold' }}">
                                {{ $notif->titulo }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                {{ $notif->mensaje }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ $notif->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No hay notificaciones.
                </div>
            @endforelse
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('notificaciones.index') }}"
               class="block px-4 py-2 text-center text-sm text-indigo-600 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                Ver todas las notificaciones
            </a>
        </div>
    </div>
</div>
