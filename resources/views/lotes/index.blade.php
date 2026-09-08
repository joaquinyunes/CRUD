@extends('layouts.app')

@section('page_title', 'Lotes y vencimientos')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Lotes y vencimientos</h1>
    <a href="{{ route('stock.index') }}" class="r-btn r-btn-ghost r-btn-sm">Stock</a>
</div>

@if($errors->any())
    <div class="r-flash-error r-mb-4"><ul style="list-style:disc;padding-left:1.2em;margin:0;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

@if($porVencer->count())
<div class="r-card-flat r-mb-6" style="border-left:3px solid #dc2626;">
    <h2 class="r-label r-mb-3" style="color:#dc2626;">Por vencer ({{ $porVencer->count() }})</h2>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Producto</th><th>Lote</th><th>Depósito</th><th>Vence</th><th style="text-align:right;">Días</th><th style="text-align:right;">Cantidad</th></tr></thead>
            <tbody>
            @foreach($porVencer as $l)
                @php $d = (int) now()->startOfDay()->diffInDays($l->vencimiento, false); @endphp
                <tr>
                    <td>{{ $l->producto?->nombre }}</td>
                    <td class="r-caption">{{ $l->lote ?? '—' }}</td>
                    <td class="r-caption">{{ $l->deposito?->nombre }}</td>
                    <td>{{ $l->vencimiento->format('d/m/Y') }}</td>
                    <td style="text-align:right;"><span class="r-tag {{ $d < 0 ? 'r-tag-danger' : '' }}">{{ $d < 0 ? 'vencido' : $d.' d' }}</span></td>
                    <td style="text-align:right;">{{ rtrim(rtrim(number_format($l->cantidad,3),'0'),'.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="r-card-flat r-mb-6">
    <h2 class="r-label r-mb-3">Registrar lote</h2>
    <form method="POST" action="{{ route('lotes.store') }}" class="r-flex r-gap-3" style="flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div style="flex:1;min-width:180px;">
            <label class="r-label">Producto</label>
            <select name="producto_id" class="r-select" required>
                <option value="">—</option>
                @foreach($productos as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach
            </select>
        </div>
        <div><label class="r-label">Depósito</label>
            <select name="deposito_id" class="r-select" required>
                @foreach($depositos as $dep)<option value="{{ $dep->id }}">{{ $dep->nombre }}</option>@endforeach
            </select>
        </div>
        <div style="width:8rem;"><label class="r-label">Lote</label><input type="text" name="lote" class="r-input"></div>
        <div><label class="r-label">Vencimiento</label><input type="date" name="vencimiento" class="r-input"></div>
        <div style="width:7rem;"><label class="r-label">Cantidad</label><input type="number" step="0.001" min="0.001" name="cantidad" class="r-input" required></div>
        <button class="r-btn r-btn-primary r-btn-sm">Registrar</button>
    </form>
    <p class="r-caption r-mt-2" style="text-transform:none;letter-spacing:0;">Sólo productos con control de vencimiento activado. El ledger de lotes es informativo y se consume FEFO al vender.</p>
</div>

<div class="r-card-flat">
    <h2 class="r-label r-mb-3">Lotes con stock</h2>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Producto</th><th>Lote</th><th>Depósito</th><th>Vence</th><th style="text-align:right;">Cantidad</th></tr></thead>
            <tbody>
            @forelse($lotes as $l)
                <tr>
                    <td>{{ $l->producto?->nombre }}</td>
                    <td class="r-caption">{{ $l->lote ?? '—' }}</td>
                    <td class="r-caption">{{ $l->deposito?->nombre }}</td>
                    <td>{{ $l->vencimiento?->format('d/m/Y') ?? '—' }}</td>
                    <td style="text-align:right;">{{ rtrim(rtrim(number_format($l->cantidad,3),'0'),'.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--color-ink-soft);">Sin lotes cargados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($lotes->hasPages())<div class="r-mt-4">{{ $lotes->links() }}</div>@endif
</div>
@endsection
