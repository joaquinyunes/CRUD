@extends('layouts.app')

@section('page_title', 'Mermas')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Mermas</h1>
    <a href="{{ route('recuentos.index') }}" class="r-btn r-btn-ghost r-btn-sm">Recuentos de inventario</a>
</div>

@if($errors->any())
    <div class="r-flash-error r-mb-4"><ul style="list-style:disc;padding-left:1.2em;margin:0;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="r-card-flat r-mb-6">
    <h2 class="r-label r-mb-3">Registrar merma <span class="r-caption" style="text-transform:none;">— descuenta stock con motivo</span></h2>
    <form method="POST" action="{{ route('mermas.store') }}" class="r-flex r-gap-3" style="flex-wrap:wrap;align-items:flex-end;">
        @csrf
        <div style="flex:1;min-width:180px;"><label class="r-label">Producto</label>
            <select name="producto_id" class="r-select" required>
                <option value="">—</option>
                @foreach($productos as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach
            </select>
        </div>
        <div><label class="r-label">Depósito</label>
            <select name="deposito_id" class="r-select" required>
                @foreach($depositos as $d)<option value="{{ $d->id }}">{{ $d->nombre }}</option>@endforeach
            </select>
        </div>
        <div style="width:6rem;"><label class="r-label">Cantidad</label><input type="number" min="1" name="cantidad" class="r-input" required></div>
        <div><label class="r-label">Motivo</label>
            <select name="motivo" class="r-select" required>
                <option value="rotura">Rotura</option>
                <option value="vencimiento">Vencimiento</option>
                <option value="robo">Robo / faltante</option>
                <option value="consumo_interno">Consumo interno</option>
                <option value="ajuste">Ajuste</option>
            </select>
        </div>
        <div style="flex:1;min-width:160px;"><label class="r-label">Observaciones</label><input type="text" name="observaciones" class="r-input"></div>
        <button class="r-btn r-btn-primary r-btn-sm">Registrar</button>
    </form>
</div>

<div class="r-card-flat">
    <div class="r-flex r-justify-between r-items-center r-mb-3">
        <h2 class="r-label" style="margin:0;">Historial</h2>
        <span class="r-caption" style="text-transform:none;letter-spacing:0;">Costo del mes: <strong>${{ number_format($totalMes, 2) }}</strong></span>
    </div>
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Fecha</th><th>Producto</th><th>Depósito</th><th>Motivo</th><th style="text-align:right;">Cant.</th><th style="text-align:right;">Costo</th><th>Quién</th></tr></thead>
            <tbody>
            @forelse($mermas as $m)
                <tr>
                    <td class="r-caption">{{ $m->created_at->format('d/m/y H:i') }}</td>
                    <td>{{ $m->producto?->nombre }}</td>
                    <td class="r-caption">{{ $m->deposito?->nombre }}</td>
                    <td><span class="r-tag">{{ str_replace('_', ' ', $m->motivo) }}</span></td>
                    <td style="text-align:right;">{{ $m->cantidad }}</td>
                    <td style="text-align:right;">${{ number_format($m->costo, 2) }}</td>
                    <td class="r-caption">{{ $m->user?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--color-ink-soft);">Sin mermas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($mermas->hasPages())<div class="r-mt-4">{{ $mermas->links() }}</div>@endif
</div>
@endsection
