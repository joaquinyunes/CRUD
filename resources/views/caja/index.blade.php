@extends('layouts.app')

@section('page_title', 'Caja')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">Caja</h2>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

@if(! $sesion)
    <div class="r-card-flat" style="max-width:420px;">
        <h3 class="r-label r-mb-4">Abrir caja</h3>
        <form method="POST" action="{{ route('caja.abrir') }}" class="r-gap-3">
            @csrf
            <div><label class="r-label">Monto inicial (efectivo en caja)</label>
                <input type="number" step="0.01" min="0" name="monto_inicial" class="r-input" value="0" required></div>
            <div><label class="r-label">Observaciones</label>
                <input type="text" name="observaciones" class="r-input" placeholder="Opcional"></div>
            <button class="r-btn r-btn-primary r-mt-3">Abrir caja</button>
        </form>
    </div>
@else
    <div class="r-flex r-gap-4 r-mb-6" style="flex-wrap:wrap;">
        <div class="r-card-flat" style="flex:1; min-width:160px;">
            <span class="r-caption">Inicial</span>
            <div class="r-display-m">${{ number_format($sesion->monto_inicial, 2, ',', '.') }}</div>
        </div>
        <div class="r-card-flat" style="flex:1; min-width:160px;">
            <span class="r-caption">Ingresos</span>
            <div class="r-display-m" style="color:#059669;">${{ number_format($sesion->totalIngresos(), 2, ',', '.') }}</div>
        </div>
        <div class="r-card-flat" style="flex:1; min-width:160px;">
            <span class="r-caption">Egresos</span>
            <div class="r-display-m" style="color:#dc2626;">${{ number_format($sesion->totalEgresos(), 2, ',', '.') }}</div>
        </div>
        <div class="r-card-flat" style="flex:1; min-width:160px;">
            <span class="r-caption">Saldo esperado</span>
            <div class="r-display-m">${{ number_format($sesion->saldoEsperado(), 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="r-flex r-gap-4 r-mb-6" style="flex-wrap:wrap;">
        <div class="r-card-flat" style="flex:1; min-width:280px;">
            <h3 class="r-label r-mb-4">Ingreso / egreso manual</h3>
            <form method="POST" action="{{ route('caja.movimiento') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
                @csrf
                <div><label class="r-label">Tipo</label>
                    <select name="tipo" class="r-select"><option value="ingreso">Ingreso</option><option value="egreso">Egreso</option></select></div>
                <div style="flex:1; min-width:140px;"><label class="r-label">Concepto</label>
                    <input type="text" name="concepto" class="r-input" required></div>
                <div style="width:120px;"><label class="r-label">Monto</label>
                    <input type="number" step="0.01" min="0.01" name="monto" class="r-input" required></div>
                <button class="r-btn r-btn-accent r-btn-sm">Registrar</button>
            </form>
        </div>
        <div class="r-card-flat" style="flex:1; min-width:280px;">
            <h3 class="r-label r-mb-4">Cerrar caja (arqueo)</h3>
            <form method="POST" action="{{ route('caja.cerrar') }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;"
                  onsubmit="return confirm('¿Cerrar la caja?');">
                @csrf
                <div style="width:150px;"><label class="r-label">Efectivo contado</label>
                    <input type="number" step="0.01" min="0" name="monto_final_declarado" class="r-input" required></div>
                <div style="flex:1; min-width:140px;"><label class="r-label">Observaciones</label>
                    <input type="text" name="observaciones" class="r-input"></div>
                <button class="r-btn r-btn-primary r-btn-sm">Cerrar</button>
            </form>
        </div>
    </div>

    <div class="r-card-flat">
        <h3 class="r-label r-mb-4">Movimientos de la sesión</h3>
        <div style="overflow-x:auto;">
            <table class="r-table">
                <thead><tr><th>Hora</th><th>Tipo</th><th>Concepto</th><th>Usuario</th><th style="text-align:right;">Monto</th></tr></thead>
                <tbody>
                @forelse($sesion->movimientos->sortByDesc('id') as $m)
                    <tr>
                        <td>{{ $m->created_at->format('d/m H:i') }}</td>
                        <td><span class="r-tag {{ $m->tipo === 'ingreso' ? 'r-tag-success' : '' }}">{{ ucfirst($m->tipo) }}</span></td>
                        <td>{{ $m->concepto }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $m->user->name ?? '—' }}</td>
                        <td style="text-align:right; font-weight:600; color:{{ $m->tipo === 'ingreso' ? '#059669' : '#dc2626' }};">
                            {{ $m->tipo === 'ingreso' ? '+' : '−' }}${{ number_format($m->monto, 2, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center; padding:var(--space-6); color:var(--color-ink-soft);">Sin movimientos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($historial->count())
    <div class="r-card-flat r-mt-6">
        <h3 class="r-label r-mb-4">Cierres recientes</h3>
        <div style="overflow-x:auto;">
            <table class="r-table">
                <thead><tr><th>Cerrada</th><th>Usuario</th><th style="text-align:right;">Sistema</th><th style="text-align:right;">Declarado</th><th style="text-align:right;">Diferencia</th></tr></thead>
                <tbody>
                @foreach($historial as $h)
                    <tr>
                        <td>{{ $h->cerrada_en?->format('d/m/Y H:i') }}</td>
                        <td style="color:var(--color-ink-soft);">{{ $h->user->name ?? '—' }}</td>
                        <td style="text-align:right;">${{ number_format($h->monto_final_sistema, 2, ',', '.') }}</td>
                        <td style="text-align:right;">${{ number_format($h->monto_final_declarado, 2, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:{{ abs($h->diferencia) < 0.01 ? 'inherit' : '#dc2626' }};">
                            ${{ number_format($h->diferencia, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
