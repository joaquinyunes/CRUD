@props([
    'campo'   => null,   // alias de ordenamiento; si es null la columna no se ordena
    'align'   => null,   // left (default) | right | center
    'inicial' => 'asc',  // direccion con la que arranca la columna al primer click
    'width'   => null,
])

@php
    $alineacion = in_array($align, ['right', 'center'], true) ? $align : 'left';
    $estilo = trim(($width ? "width:{$width};" : '').($alineacion !== 'left' ? "text-align:{$alineacion};" : ''));

    $ordenActual = request()->query('orden');
    $dirActual   = strtolower((string) request()->query('dir')) === 'desc' ? 'desc' : 'asc';
    $activa      = $campo !== null && $ordenActual === $campo;

    // Al clickear: si ya esta activa invierte; si no, arranca con su direccion natural.
    $proximaDir = $activa
        ? ($dirActual === 'asc' ? 'desc' : 'asc')
        : (strtolower($inicial) === 'desc' ? 'desc' : 'asc');

    $url = $campo ? request()->fullUrlWithQuery(['orden' => $campo, 'dir' => $proximaDir, 'page' => null]) : null;
@endphp

<th @if($estilo) style="{{ $estilo }}" @endif {{ $attributes }}>
    @if($campo)
        <a href="{{ $url }}"
           class="r-th-sort {{ $activa ? 'is-active' : '' }}"
           data-dir="{{ $activa ? $dirActual : '' }}"
           title="Ordenar por {{ strip_tags($slot) }} ({{ $proximaDir === 'asc' ? 'ascendente' : 'descendente' }})">
            <span>{{ $slot }}</span>
            <svg class="r-th-arrow" width="10" height="12" viewBox="0 0 10 12" aria-hidden="true">
                <path class="r-th-arrow-up" d="M5 1.5 L8.2 5 L1.8 5 Z"/>
                <path class="r-th-arrow-down" d="M5 10.5 L1.8 7 L8.2 7 Z"/>
            </svg>
        </a>
    @else
        {{ $slot }}
    @endif
</th>
