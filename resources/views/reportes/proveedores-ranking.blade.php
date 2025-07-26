@extends('layouts.app')

@section('page_title', 'Ranking de Proveedores')

@section('content')
<div class="r-mb-lg">

    <div class="r-flex r-items-center r-justify-between r-mb-lg">
        <h1 class="r-display-l">Ranking de Proveedores</h1>
        <a href="{{ route('reportes.index') }}" class="r-caption">&larr; Volver</a>
    </div>

    <div class="r-card-flat r-mb-lg">
        <div class="r-flex r-gap-sm">
            <a href="{{ route('reportes.proveedores-ranking', ['limit' => 5]) }}"
               class="r-btn {{ $limit == 5 ? 'r-btn--primary' : 'r-btn--subtle' }}">
                Top 5
            </a>
            <a href="{{ route('reportes.proveedores-ranking', ['limit' => 10]) }}"
               class="r-btn {{ $limit == 10 ? 'r-btn--primary' : 'r-btn--subtle' }}">
                Top 10
            </a>
            <a href="{{ route('reportes.proveedores-ranking', ['limit' => 20]) }}"
               class="r-btn {{ $limit == 20 ? 'r-btn--primary' : 'r-btn--subtle' }}">
                Top 20
            </a>
        </div>
    </div>

    <div class="r-card-flat" data-reveal>
        <table class="r-table">
            <thead>
                <tr>
                    <th class="r-text-center" style="width:3rem">#</th>
                    <th>Proveedor</th>
                    <th>Contacto</th>
                    <th class="r-text-center">Compras</th>
                    <th class="r-text-right">Total gastado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos as $index => $item)
                    <tr>
                        <td class="r-text-center r-caption">{{ $index + 1 }}</td>
                        <td style="font-weight:500;">{{ $item->nombre }}</td>
                        <td class="r-caption">{{ $item->email ?? '—' }} {{ $item->telefono ? '| ' . $item->telefono : '' }}</td>
                        <td class="r-text-center">{{ $item->total_compras }}</td>
                        <td class="r-text-right" style="font-weight:600;">${{ number_format($item->total_gastado, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="r-text-center r-caption">No hay compras registradas.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($datos->count())
                <tfoot>
                    <tr>
                        <td colspan="3" class="r-label">Total</td>
                        <td class="r-text-center r-label">{{ $datos->sum('total_compras') }}</td>
                        <td class="r-text-right r-label">${{ number_format($datos->sum('total_gastado'), 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

</div>
@endsection
