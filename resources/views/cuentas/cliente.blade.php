@extends('layouts.app')

@section('page_title', 'Cuenta · ' . $cliente->nombre)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">{{ $cliente->nombre }} {{ $cliente->apellido }}</h2>
    <a href="{{ route('cuentas.clientes') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="r-flex r-gap-4 r-mb-6" style="flex-wrap:wrap;">
    <div class="r-card-flat" style="flex:1; min-width:180px;">
        <span class="r-caption">{{ $saldo < 0 ? 'Saldo a favor del cliente' : 'Deuda actual' }}</span>
        <div class="r-display-m" style="color:{{ $saldo > 0 ? '#dc2626' : ($saldo < 0 ? '#059669' : 'inherit') }};">
            ${{ number_format(abs($saldo), 2, ',', '.') }}
        </div>
    </div>
    <div class="r-card-flat" style="flex:1; min-width:180px;">
        <span class="r-caption">Límite de crédito</span>
        <div class="r-display-m">${{ number_format($cliente->limite_credito, 2, ',', '.') }}</div>
    </div>
</div>

<div class="r-card-flat r-mb-6">
    <h3 class="r-label r-mb-4">Registrar cobro</h3>
    <form method="POST" action="{{ route('cuentas.cliente.cobrar', $cliente) }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        @csrf
        <div style="min-width:200px;">
            <label class="r-label">Aplicar a</label>
            <select name="venta_id" class="r-select">
                <option value="">Deuda más antigua (FIFO)</option>
                @foreach($ventas->where('estado_pago', '!=', 'pagado') as $v)
                    @if($v->saldoPendiente() > 0)
                        <option value="{{ $v->id }}">{{ $v->numero }} · saldo ${{ number_format($v->saldoPendiente(), 2, ',', '.') }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <label class="r-label">Método</label>
            <select name="metodo_pago_id" class="r-select" required>
                @foreach($metodosPago as $mp)<option value="{{ $mp->id }}">{{ $mp->nombre }}</option>@endforeach
            </select>
        </div>
        <div style="width:140px;">
            <label class="r-label">Monto</label>
            <input type="number" step="0.01" min="0.01" name="monto" class="r-input" required>
        </div>
        <div style="width:160px;">
            <label class="r-label">Referencia</label>
            <input type="text" name="referencia" class="r-input" placeholder="Opcional">
        </div>
        <button class="r-btn r-btn-primary r-btn-sm">Cobrar</button>
    </form>
</div>

<div class="r-card-flat">
    <h3 class="r-label r-mb-4">Ventas</h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr>
                <th>Número</th><th>Fecha</th><th>Estado</th>
                <th style="text-align:right;">Total</th>
                <th style="text-align:right;">Pagado</th>
                <th style="text-align:right;">Devuelto</th>
                <th style="text-align:right;">Saldo</th>
            </tr></thead>
            <tbody>
            @forelse($ventas as $v)
                <tr>
                    <td>{{ $v->numero }}</td>
                    <td>{{ $v->fecha->format('d/m/Y') }}</td>
                    <td><span class="r-tag">{{ ucfirst($v->estado_pago) }}</span></td>
                    <td style="text-align:right;">${{ number_format($v->total_final, 2, ',', '.') }}</td>
                    <td style="text-align:right;">${{ number_format($v->pagado, 2, ',', '.') }}</td>
                    <td style="text-align:right;">${{ number_format($v->totalDevuelto(), 2, ',', '.') }}</td>
                    <td style="text-align:right; font-weight:600;">${{ number_format($v->saldoPendiente(), 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:var(--space-6); color:var(--color-ink-soft);">Sin ventas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
