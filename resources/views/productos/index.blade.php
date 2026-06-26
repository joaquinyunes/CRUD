@extends('layouts.app')

@section('page_title', 'Productos')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Productos</h1>

            @if (auth()->user()->role?->tienePermiso('productos.crear'))
                <a href="{{ route('productos.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500">
                    Nuevo producto
                </a>
            @endif
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 dark:bg-green-900/40 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
            <form method="GET" action="{{ route('productos.index') }}" class="flex flex-wrap gap-3 items-end">
                <div class="min-w-[200px]">
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Buscar</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Nombre, código o marca…"
                           class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm w-full">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Categoría</label>
                    <select name="categoria_id" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todas</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 dark:text-gray-300">Estado</label>
                    <select name="estado" class="mt-1 rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 text-sm">
                        <option value="">Todos</option>
                        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-100 text-sm rounded-md hover:bg-gray-300">
                    Filtrar
                </button>

                @if(request()->hasAny(['buscar','categoria_id','estado']))
                    <a href="{{ route('productos.index') }}" class="text-sm text-gray-500 hover:underline">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 w-12"></th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Código</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Categoría</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Marca</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">P. Compra</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">P. Venta</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stock</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($productos as $producto)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-4 py-2">
                                @if($producto->imagen)
                                    <img src="{{ asset('storage/' . $producto->imagen) }}"
                                         alt="{{ $producto->nombre }}"
                                         class="rounded"
                                         style="width:40px;height:40px;object-fit:cover;">
                                @else
                                    <div class="bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center"
                                         style="width:40px;height:40px;">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $producto->codigo }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800 dark:text-gray-100">{{ $producto->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $producto->categoria->nombre ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $producto->marca ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($producto->precio_compra, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 text-right">${{ number_format($producto->precio_venta, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($producto->estaEnStockCritico())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ $producto->stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ $producto->stock }}
                                    </span>
                                @endif
                                <span class="text-gray-400 text-xs ml-1">(mín: {{ $producto->stock_minimo }})</span>
                            </td>
                            <td class="px-4 py-2 text-sm text-center">
                                @if($producto->estado === 'activo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Activo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if (auth()->user()->role?->tienePermiso('productos.editar'))
                                        <a href="{{ route('productos.edit', $producto) }}" class="text-indigo-600 hover:underline">Editar</a>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('productos.crear'))
                                        <form method="POST" action="{{ route('productos.duplicar', $producto) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:underline"
                                                    onclick="return confirm('¿Duplicar este producto?')">Duplicar</button>
                                        </form>
                                    @endif

                                    @if (auth()->user()->role?->tienePermiso('productos.eliminar'))
                                        <form method="POST" action="{{ route('productos.destroy', $producto) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline"
                                                    onclick="return confirm('¿Eliminar este producto?')">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron productos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($productos->hasPages())
            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }}
                    de {{ $productos->total() }} productos
                </span>
                {{ $productos->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
