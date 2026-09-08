@extends('layouts.app')

@section('page_title', 'Recuentos de inventario')

@section('content')
<div class="r-flex r-items-center r-justify-between r-mb-6">
    <h1 class="r-display-l">Recuentos de inventario</h1>
    <a href="{{ route('recuentos.create') }}" class="r-btn r-btn-primary r-btn-sm">Nuevo recuento</a>
</div>

<div class="r-card-flat">
    <div style="overflow-x:auto;">
        <table class="r-table">
            <thead><tr><th>Número</th><th>Depósito</th><th>Ítems</th><th>Estado</th><th>Creado</th><th>Aplicado</th><th></th></tr></thead>
            <tbody>
            @forelse($recuentos as $r)
                <tr>
                    <td style="font-weight:500;">{{ $r->numero }}</td>
                    <td>{{ $r->deposito?->nombre }}</td>
                    <td>{{ $r->detalles_count }}</td>
                    <td>
                        @if($r->estado === 'aplicado')<span class="r-tag r-tag-success">Aplicado</span>
                        @elseif($r->estado === 'anulado')<span class="r-tag">Anulado</span>
                        @else<span class="r-tag" style="background:#fef3c7;color:#b45309;">Abierto</span>@endif
                    </td>
                    <td class="r-caption">{{ $r->created_at->format('d/m/y H:i') }}</td>
                    <td class="r-caption">{{ $r->aplicado_en?->format('d/m/y H:i') ?? '—' }}</td>
                    <td style="text-align:right;"><a href="{{ route('recuentos.show', $r) }}" class="r-btn r-btn-ghost r-btn-sm" style="font-size:0.75rem;padding:4px 12px;">Abrir</a></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--color-ink-soft);">Sin recuentos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($recuentos->hasPages())<div class="r-mt-4">{{ $recuentos->links() }}</div>@endif
</div>
@endsection
