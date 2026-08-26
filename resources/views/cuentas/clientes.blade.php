@extends('layouts.app')

@section('page_title', 'Cuenta corriente · Clientes')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Cuenta corriente · Clientes</h2>
    <a href="{{ route('cuentas.proveedores') }}" class="r-btn r-btn-ghost r-btn-sm">Ver proveedores →</a>
</div>

<div class="r-card-flat r-mb-6">
    <form method="GET" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:220px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, apellido o documento…" class="r-input">
        </div>
        <label class="r-flex r-gap-2 r-items-center"><input type="checkbox" name="con_saldo" value="1" @checked(request('con_saldo'))> Solo con deuda</label>
        <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        <a href="{{ route('cuentas.clientes') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
    </form>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr>
                <th>Cliente</th><th>Documento</th>
                <th style="text-align:right;">Límite crédito</th>
                <th style="text-align:right;">Saldo (deuda)</th>
                <th style="text-align:right;">Disponible</th>
                <th></th>
            </tr></thead>
            <tbody>
            @forelse ($clientes as $c)
                <tr>
                    <td style="font-weight:500;">{{ $c->nombre }} {{ $c->apellido }}</td>
                    <td style="color:var(--color-ink-soft);">{{ $c->documento ?? '—' }}</td>
                    <td style="text-align:right;">${{ number_format($c->limite_credito, 2, ',', '.') }}</td>
                    <td style="text-align:right; font-weight:600; color:{{ $c->saldo_actual > 0 ? '#dc2626' : 'inherit' }};">
                        ${{ number_format($c->saldo_actual, 2, ',', '.') }}
                    </td>
                    <td style="text-align:right;">
                        @if ($c->limite_credito > 0)
                            ${{ number_format($c->limite_credito - $c->saldo_actual, 2, ',', '.') }}
                        @else — @endif
                    </td>
                    <td style="text-align:right;"><a href="{{ route('cuentas.cliente', $c) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Ver / cobrar</a></td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin clientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
