@extends('layouts.app')

@section('page_title', 'Cuenta · ' . $proveedor->nombre)

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-6" data-reveal="fade-up">
    <h2 class="r-display-l">{{ $proveedor->nombre }}</h2>
    <a href="{{ route('cuentas.proveedores') }}" class="r-btn r-btn-ghost r-btn-sm">← Volver</a>
</div>

@if($errors->any())
    <div class="r-card-flat r-mb-4" style="border-left:3px solid #dc2626;">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="r-card-flat r-mb-6" style="max-width:280px;">
    <span class="r-caption">Deuda actual</span>
    <div class="r-display-m" style="color:{{ $saldo > 0 ? '#dc2626' : 'inherit' }};">${{ number_format($saldo, 2, ',', '.') }}</div>
</div>

<div class="r-card-flat r-mb-6">
    <h3 class="r-label r-mb-4">Registrar pago</h3>
    <form method="POST" action="{{ route('cuentas.proveedor.pagar', $proveedor) }}" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        @csrf
        <div style="min-width:200px;">
            <label class="r-label">Aplicar a</label>
            <select name="compra_id" class="r-select">
                <option value="">Deuda más antigua (FIFO)</option>
                @foreach($compras as $c)
                    @if($c->saldoPendiente() > 0)
                        <option value="{{ $c->id }}">{{ $c->numero }} · saldo ${{ number_format($c->saldoPendiente(), 2, ',', '.') }}</option>
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
        <button class="r-btn r-btn-primary r-btn-sm">Pagar</button>
    </form>
</div>

<div class="r-card-flat">
    <h3 class="r-label r-mb-4">Compras</h3>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr>
                <th>Número</th><th>Fecha</th><th>Pago</th>
                <th style="text-align:right;">Total</th>
                <th style="text-align:right;">Pagado</th>
                <th style="text-align:right;">Saldo</th>
            </tr></thead>
            <tbody>
            @forelse($compras as $c)
                <tr>
                    <td>{{ $c->numero }}</td>
                    <td>{{ $c->fecha->format('d/m/Y') }}</td>
                    <td><span class="r-tag">{{ ucfirst($c->estado_pago) }}</span></td>
                    <td style="text-align:right;">${{ number_format($c->total_final, 2, ',', '.') }}</td>
                    <td style="text-align:right;">${{ number_format($c->pagado, 2, ',', '.') }}</td>
                    <td style="text-align:right; font-weight:600;">${{ number_format($c->saldoPendiente(), 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:var(--space-6); color:var(--color-ink-soft);">Sin compras.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
