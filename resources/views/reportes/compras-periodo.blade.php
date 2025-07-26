@extends('layouts.app')

@section('page_title', 'Compras por período')

@section('content')
<div class="r-mb-lg">

    <div class="r-flex r-items-center r-justify-between r-mb-lg">
        <h1 class="r-display-l">Compras por período</h1>
        <a href="{{ route('reportes.index') }}" class="r-caption">&larr; Volver</a>
    </div>

    <div class="r-card-flat r-mb-lg">
        <div class="r-flex r-gap-sm">
            <a href="{{ route('reportes.compras-periodo', ['periodo' => 'diario']) }}"
               class="r-btn {{ $periodo == 'diario' ? 'r-btn--primary' : 'r-btn--subtle' }}">
                Diario
            </a>
            <a href="{{ route('reportes.compras-periodo', ['periodo' => 'semanal']) }}"
               class="r-btn {{ $periodo == 'semanal' ? 'r-btn--primary' : 'r-btn--subtle' }}">
                Semanal
            </a>
            <a href="{{ route('reportes.compras-periodo', ['periodo' => 'mensual']) }}"
               class="r-btn {{ $periodo == 'mensual' ? 'r-btn--primary' : 'r-btn--subtle' }}">
                Mensual
            </a>
        </div>
    </div>

    <div class="r-card-flat" data-reveal>
        <table class="r-table">
            <thead>
                <tr>
                    <th>Período</th>
                    <th class="r-text-center">Compras</th>
                    <th class="r-text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($datos as $item)
                    <tr>
                        <td>
                            @if(is_string($item->periodo))
                                {{ \Carbon\Carbon::parse($item->periodo)->format('d/m/Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($item->periodo)->format('d/m/Y') }}
                            @endif
                        </td>
                        <td class="r-text-center">{{ $item->cantidad }}</td>
                        <td class="r-text-right">${{ number_format($item->total, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="r-text-center r-caption">No hay compras registradas en este período.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($datos->count())
                <tfoot>
                    <tr>
                        <td class="r-label">Total</td>
                        <td class="r-text-center r-label">{{ $datos->sum('cantidad') }}</td>
                        <td class="r-text-right r-label">${{ number_format($datos->sum('total'), 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

</div>
@endsection
