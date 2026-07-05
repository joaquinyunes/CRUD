@extends('layouts.app')

@section('page_title', 'Stock crítico')

@section('content')
<div class="r-mb-lg">
    <div class="r-mb-lg">

        <div class="r-flex r-items-center r-justify-between r-mb-lg">
            <h1 class="r-display-l">Stock crítico</h1>
            <a href="{{ route('reportes.index') }}" class="r-caption">
                &larr; Volver
            </a>
        </div>

        <div class="r-card-flat r-mb-lg" data-reveal>
            <h2 class="r-label r-mb-md" style="color: var(--r-color-red-600, #dc2626)">
                Productos agotados ({{ $agotados->count() }})
            </h2>

            @if($agotados->count())
                <table class="r-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="r-text-center">Stock</th>
                            <th class="r-text-center">Mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agotados as $prod)
                            <tr>
                                <td class="r-caption"><code>{{ $prod->codigo }}</code></td>
                                <td>{{ $prod->nombre }}</td>
                                <td class="r-caption">{{ $prod->categoria->nombre ?? '—' }}</td>
                                <td class="r-text-center">
                                    <span class="r-tag r-tag--danger">0</span>
                                </td>
                                <td class="r-text-center r-caption">{{ $prod->stock_minimo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="r-caption">No hay productos agotados.</p>
            @endif
        </div>

        <div class="r-card-flat" data-reveal>
            <h2 class="r-label r-mb-md" style="color: var(--r-color-yellow-600, #ca8a04)">
                Productos con stock bajo ({{ $criticos->count() }})
            </h2>

            @if($criticos->count())
                <table class="r-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="r-text-center">Stock</th>
                            <th class="r-text-center">Mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($criticos as $prod)
                            <tr>
                                <td class="r-caption"><code>{{ $prod->codigo }}</code></td>
                                <td>{{ $prod->nombre }}</td>
                                <td class="r-caption">{{ $prod->categoria->nombre ?? '—' }}</td>
                                <td class="r-text-center">
                                    <span class="r-tag r-tag--warning">{{ $prod->stock }}</span>
                                </td>
                                <td class="r-text-center r-caption">{{ $prod->stock_minimo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="r-caption">No hay productos con stock crítico.</p>
            @endif
        </div>

    </div>
</div>
@endsection
