@extends('layouts.app')

@section('page_title', 'Productos más vendidos')

@section('content')
<div class="r-mb-lg">
    <div class="r-mb-lg">

        <div class="r-flex r-items-center r-justify-between r-mb-lg">
            <h1 class="r-display-l">Productos más vendidos</h1>
            <a href="{{ route('reportes.index') }}" class="r-caption">
                &larr; Volver
            </a>
        </div>

        <div class="r-card-flat r-mb-lg">
            <div class="r-flex r-gap-sm">
                <a href="{{ route('reportes.productos-vendidos', ['limit' => 5]) }}"
                   class="r-btn {{ $limit == 5 ? 'r-btn--primary' : 'r-btn--subtle' }}">
                    Top 5
                </a>
                <a href="{{ route('reportes.productos-vendidos', ['limit' => 10]) }}"
                   class="r-btn {{ $limit == 10 ? 'r-btn--primary' : 'r-btn--subtle' }}">
                    Top 10
                </a>
                <a href="{{ route('reportes.productos-vendidos', ['limit' => 20]) }}"
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
                        <th>Código</th>
                        <th>Producto</th>
                        <th class="r-text-center">Unidades vendidas</th>
                        <th class="r-text-right">Total facturado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos as $index => $item)
                        <tr>
                            <td class="r-text-center r-caption">{{ $index + 1 }}</td>
                            <td class="r-caption"><code>{{ $item->codigo }}</code></td>
                            <td>{{ $item->nombre }}</td>
                            <td class="r-text-center r-body">{{ $item->total_vendido }}</td>
                            <td class="r-text-right r-body">
                                ${{ number_format($item->total_facturado, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="r-text-center r-caption">
                                No hay ventas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($datos->count())
                    <tfoot>
                        <tr>
                            <td colspan="3" class="r-label">Total</td>
                            <td class="r-text-center r-label">
                                {{ $datos->sum('total_vendido') }}
                            </td>
                            <td class="r-text-right r-label">
                                ${{ number_format($datos->sum('total_facturado'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

    </div>
</div>
@endsection
