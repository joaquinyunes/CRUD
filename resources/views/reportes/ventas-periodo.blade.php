@extends('layouts.app')

@section('page_title', 'Ventas por período')

@section('content')
<div class="r-mb-lg">
    <div class="r-mb-lg">

        <div class="r-flex r-items-center r-justify-between r-mb-lg">
            <h1 class="r-display-l">Ventas por período</h1>
            <div class="r-flex r-items-center r-gap-sm">
                @if (auth()->user()->role?->tienePermiso('ventas.exportar'))
                    <a href="{{ route('exportar.ventas', ['formato' => 'xlsx']) }}"
                       class="r-btn r-btn--primary r-btn--success">
                        Exportar Excel
                    </a>
                @endif
                <a href="{{ route('reportes.index') }}" class="r-caption">
                    &larr; Volver
                </a>
            </div>
        </div>

        <div class="r-card-flat r-mb-lg">
            <div class="r-flex r-gap-sm">
                <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'diario']) }}"
                   class="r-btn {{ $periodo === 'diario' ? 'r-btn--primary' : 'r-btn--subtle' }}">
                    Diario
                </a>
                <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'semanal']) }}"
                   class="r-btn {{ $periodo === 'semanal' ? 'r-btn--primary' : 'r-btn--subtle' }}">
                    Semanal
                </a>
                <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'mensual']) }}"
                   class="r-btn {{ $periodo === 'mensual' ? 'r-btn--primary' : 'r-btn--subtle' }}">
                    Mensual
                </a>
            </div>
        </div>

        <div class="r-card-flat" data-reveal>
            <table class="r-table">
                <thead>
                    <tr>
                        <th>
                            {{ $periodo === 'semanal' ? 'Semana' : 'Período' }}
                        </th>
                        <th class="r-text-center">Cantidad</th>
                        <th class="r-text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($datos as $item)
                        <tr>
                            <td>
                                @if($periodo === 'diario')
                                    {{ \Carbon\Carbon::parse($item->periodo)->format('d/m/Y') }}
                                @elseif($periodo === 'semanal')
                                    Semana del {{ \Carbon\Carbon::parse($item->periodo)->format('d/m/Y') }}
                                @else
                                    {{ $item->periodo }}
                                @endif
                            </td>
                            <td class="r-text-center r-caption">{{ $item->cantidad }}</td>
                            <td class="r-text-right r-body">
                                ${{ number_format($item->total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="r-text-center r-caption">
                                No hay ventas registradas en este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($datos->count())
                    <tfoot>
                        <tr>
                            <td class="r-label">Total</td>
                            <td class="r-text-center r-label">
                                {{ $datos->sum('cantidad') }}
                            </td>
                            <td class="r-text-right r-label">
                                ${{ number_format($datos->sum('total'), 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

    </div>
</div>
@endsection
