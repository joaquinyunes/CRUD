@extends('layouts.app')

@section('page_title', 'Cuenta corriente · Proveedores')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Cuenta corriente · Proveedores</h2>
    <a href="{{ route('cuentas.clientes') }}" class="r-btn r-btn-ghost r-btn-sm">← Ver clientes</a>
</div>

<div class="r-card-flat r-mb-6">
    <form method="GET" class="r-flex r-gap-3" style="flex-wrap:wrap; align-items:flex-end;">
        <div style="min-width:220px; flex:1;">
            <label class="r-label">Buscar</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, CUIT o email…" class="r-input">
        </div>
        <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
        <a href="{{ route('cuentas.proveedores') }}" class="r-btn r-btn-ghost r-btn-sm">Limpiar</a>
    </form>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr>
                <th>Proveedor</th><th>CUIT</th>
                <th style="text-align:right;">Saldo (deuda)</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($proveedores as $p)
                <tr>
                    <td style="font-weight:500;">{{ $p->nombre }}</td>
                    <td style="color:var(--color-ink-soft);">{{ $p->cuit ?? '—' }}</td>
                    <td style="text-align:right; font-weight:600; color:{{ $p->saldo_actual > 0 ? '#dc2626' : 'inherit' }};">
                        ${{ number_format($p->saldo_actual, 2, ',', '.') }}
                    </td>
                    <td style="text-align:right;"><a href="{{ route('cuentas.proveedor', $p) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Ver / pagar</a></td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin proveedores.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
