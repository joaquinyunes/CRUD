@extends('layouts.app')

@section('page_title', 'Notificaciones')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                Notificaciones
                @if($noLeidas > 0)
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        {{ $noLeidas }} nuevas
                    </span>
                @endif
            </h1>

            @if($noLeidas > 0)
                <form method="PATCH" action="{{ route('notificaciones.leer-todas') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-sm text-indigo-600 hover:underline">
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            @forelse($notificaciones as $notif)
                <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700 {{ $notif->leida ? 'bg-white dark:bg-gray-800' : 'bg-indigo-50 dark:bg-indigo-900/10' }}">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 mt-1">
                            @if($notif->tipo === 'venta')
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                            @elseif($notif->tipo === 'compra')
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </span>
                            @elseif($notif->tipo === 'stock')
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium {{ $notif->leida ? 'text-gray-600 dark:text-gray-400' : 'text-gray-800 dark:text-gray-100' }}">
                                {{ $notif->titulo }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $notif->mensaje }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                {{ $notif->created_at->format('d/m/Y H:i') }} · {{ $notif->created_at->diffForHumans() }}
                            </p>
                        </div>

                        <div class="shrink-0">
                            @if(!$notif->leida)
                                <form method="PATCH" action="{{ route('notificaciones.marcar-leida', $notif) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                        Marcar leída
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                    No hay notificaciones.
                </div>
            @endforelse
        </div>

        @if($notificaciones->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $notificaciones->firstItem() }}–{{ $notificaciones->lastItem() }}
                    de {{ $notificaciones->total() }} notificaciones
                </span>
                {{ $notificaciones->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
