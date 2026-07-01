@extends('layouts.app')

@section('page_title', 'Ventas por período')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Ventas por período</h1>
            <div class="flex items-center gap-2">
                @if (auth()->user()->role?->tienePermiso('ventas.exportar'))
                    <a href="{{ route('exportar.ventas', ['formato' => 'xlsx']) }}"
                       class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-500">
                        Exportar Excel
                    </a>
                @endif
                <a href="{{ route('reportes.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    &larr; Volver
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <div class="flex gap-2">
                <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'diario']) }}"
                   class="px-4 py-2 text-sm rounded-md {{ $periodo === 'diario' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-100 hover:bg-gray-300' }}">
                    Diario
                </a>
                <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'semanal']) }}"
                   class="px-4 py-2 text-sm rounded-md {{ $periodo === 'semanal' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-100 hover:bg-gray-300' }}">
                    Semanal
                </a>
                <a href="{{ route('reportes.ventas-periodo', ['periodo' => 'mensual']) }}"
                   class="px-4 py-2 text-sm rounded-md {{ $periodo === 'mensual' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 dark:text-gray-100 hover:bg-gray-300' }}">
                    Mensual
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            {{ $periodo === 'semanal' ? 'Semana' : 'Período' }}
                        </th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cantidad</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($datos as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">
                                @if($periodo === 'diario')
                                    {{ \Carbon\Carbon::parse($item->periodo)->format('d/m/Y') }}
                                @elseif($periodo === 'semanal')
                                    Semana del {{ \Carbon\Carbon::parse($item->periodo)->format('d/m/Y') }}
                                @else
                                    {{ $item->periodo }}
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400">{{ $item->cantidad }}</td>
                            <td class="px-4 py-2 text-sm text-right font-medium text-gray-800 dark:text-gray-100">
                                ${{ number_format($item->total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay ventas registradas en este período.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($datos->count())
                    <tfoot class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <td class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Total</td>
                            <td class="px-4 py-2 text-sm text-center font-semibold text-gray-700 dark:text-gray-300">
                                {{ $datos->sum('cantidad') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right font-semibold text-gray-700 dark:text-gray-300">
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
