@extends('layouts.app')

@section('page_title', 'Notificaciones')

@section('content')
<div style="max-width: 52rem; margin: 0 auto;">

    <div class="r-head">
        <h2 class="r-display-m">
            Notificaciones
            @if($noLeidas > 0)
                <span class="r-tag r-tag-danger" style="margin-left:8px;">{{ $noLeidas }} nuevas</span>
            @endif
        </h2>

        @if($noLeidas > 0)
            <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="r-btn r-btn-ghost r-btn-sm">Marcar todas como leídas</button>
            </form>
        @endif
    </div>

    @if (session('success'))
        <div class="r-flash-success r-mb-6">{{ session('success') }}</div>
    @endif

    <div class="r-card-flat" style="padding:0;overflow:hidden;">
        @forelse($notificaciones as $notif)
            @php
                $tono = match($notif->tipo) {
                    'venta' => ['var(--color-success)', 'var(--color-success-soft)'],
                    'compra' => ['#2563EB', 'rgba(37,99,235,0.12)'],
                    'stock' => ['var(--color-marigold-deep)', 'rgba(226,161,59,0.14)'],
                    default => ['var(--color-ink-soft)', 'var(--color-bg-muted)'],
                };
                $icono = match($notif->tipo) {
                    'venta' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'compra' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                    'stock' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z',
                    default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                };
            @endphp
            <div class="r-flex r-items-start r-gap-4" style="padding:var(--space-4);border-bottom:1px solid var(--color-line);{{ $notif->leida ? '' : 'background:rgba(226,161,59,0.05);' }}">
                <span style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:{{ $tono[1] }};margin-top:2px;">
                    <svg width="20" height="20" fill="none" stroke="{{ $tono[0] }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icono }}"/></svg>
                </span>

                <div class="r-grow" style="min-width:0;">
                    <p style="margin:0;font-size:0.9375rem;font-weight:{{ $notif->leida ? '500' : '600' }};color:{{ $notif->leida ? 'var(--color-ink-soft)' : 'var(--color-ink)' }};">
                        {{ $notif->titulo }}
                    </p>
                    <p class="r-body" style="font-size:0.875rem;margin:4px 0 0;">{{ $notif->mensaje }}</p>
                    <p class="r-caption" style="text-transform:none;letter-spacing:0;margin:8px 0 0;">
                        {{ $notif->created_at->format('d/m/Y H:i') }} · {{ $notif->created_at->diffForHumans() }}
                    </p>
                </div>

                @if(!$notif->leida)
                    <form method="POST" action="{{ route('notificaciones.marcar-leida', $notif) }}" style="flex-shrink:0;">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="r-btn r-btn-ghost r-btn-sm">Marcar leída</button>
                    </form>
                @endif
            </div>
        @empty
            <div class="r-empty">
                <p class="r-empty-title">Sin notificaciones</p>
                <p class="r-empty-text">Acá van a aparecer los avisos de ventas, compras y stock bajo.</p>
            </div>
        @endforelse
    </div>

    @if($notificaciones->hasPages())
        <div class="r-flex r-items-center r-justify-between r-mt-6" style="font-size:0.875rem;color:var(--color-ink-soft);">
            <span>Mostrando {{ $notificaciones->firstItem() }}–{{ $notificaciones->lastItem() }} de {{ $notificaciones->total() }}</span>
            {{ $notificaciones->links() }}
        </div>
    @endif

</div>
@endsection
