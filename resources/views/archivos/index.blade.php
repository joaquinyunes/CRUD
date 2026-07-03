@extends('layouts.app')

@section('page_title', 'Archivos')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Archivos</h1>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <x-file-upload />

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('archivos.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Nombre del archivo…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if(request()->has('buscar'))
                    <a href="{{ route('archivos.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 w-12"></th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tipo</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tamaño</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Relación</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subido por</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fecha</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($archivos as $archivo)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2">
                                @if($archivo->esImagen)
                                    <img src="{{ asset('storage/' . $archivo->ruta) }}"
                                         alt="{{ $archivo->nombre }}"
                                         class="rounded"
                                         style="width:40px;height:40px;object-fit:cover;">
                                @else
                                    <div class="bg-red-100 dark:bg-red-900/50 rounded flex items-center justify-center"
                                         style="width:40px;height:40px;">
                                        <span class="text-xs font-bold text-red-600 dark:text-red-400">PDF</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100 truncate max-w-[200px]">
                                {{ $archivo->nombre }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $archivo->tipo }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 text-right">
                                {{ $archivo->tamanoFormateado }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                @if($archivo->relacionado_tipo)
                                    {{ ucfirst($archivo->relacionado_tipo) }} #{{ $archivo->relacionado_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $archivo->user->name ?? '—' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $archivo->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('archivos.download', $archivo) }}"
                                       class="text-indigo-600 hover:underline">Descargar</a>

                                    <form method="POST" action="{{ route('archivos.destroy', $archivo) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline"
                                                onclick="return confirm('¿Eliminar este archivo?')">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron archivos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($archivos->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $archivos->firstItem() }}–{{ $archivos->lastItem() }}
                    de {{ $archivos->total() }} archivos
                </span>
                {{ $archivos->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
