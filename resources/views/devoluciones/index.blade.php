@extends('layouts.app')

@section('page_title', 'Devoluciones')

@section('content')
<div class="r-flex r-justify-between r-items-center r-mb-8" data-reveal="fade-up">
    <h2 class="r-display-l">Devoluciones</h2>
</div>

<div class="r-card-flat r-mb-6">
    <form method="GET" class="r-flex r-gap-3" style="align-items:flex-end;">
        <div><label class="r-label">Tipo</label>
            <select name="tipo" class="r-select">
                <option value="">Todas</option>
                <option value="venta" @selected(request('tipo')==='venta')>De ventas</option>
                <option value="compra" @selected(request('tipo')==='compra')>A proveedores</option>
            </select>
        </div>
        <button class="r-btn r-btn-accent r-btn-sm">Filtrar</button>
    </form>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr>
                <x-th campo="numero">Número</x-th><x-th campo="tipo">Tipo</x-th><th>Documento</th><th>Contraparte</th><x-th campo="fecha" inicial="desc">Fecha</x-th>
                <x-th campo="total" align="right" inicial="desc">Total</x-th><th></th>
            </tr></thead>
            <tbody>
            @forelse($devoluciones as $d)
                <tr>
                    <td>{{ $d->numero }}</td>
                    <td><span class="r-tag">{{ $d->tipo === 'venta' ? 'Venta' : 'Compra' }}</span></td>
                    <td>{{ $d->venta->numero ?? $d->compra->numero ?? '—' }}</td>
                    <td style="color:var(--color-ink-soft);">
                        {{ $d->tipo === 'venta'
                            ? trim(($d->venta->cliente->nombre ?? '') . ' ' . ($d->venta->cliente->apellido ?? ''))
                            : ($d->compra->proveedor->nombre ?? '—') }}
                    </td>
                    <td>{{ $d->fecha->format('d/m/Y') }}</td>
                    <td style="text-align:right;">${{ number_format($d->total, 2, ',', '.') }}</td>
                    <td style="text-align:right;"><a href="{{ route('devoluciones.show', $d) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:.75rem;">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:var(--space-8); color:var(--color-ink-soft);">Sin devoluciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($devoluciones->hasPages())
    <div class="r-mt-6">{{ $devoluciones->links() }}</div>
@endif
@endsection
