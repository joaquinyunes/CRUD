@if ($paginator->hasPages())
    <nav class="r-pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Anterior" class="is-disabled">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span aria-disabled="true" class="is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente">&rsaquo;</a>
        @else
            <span aria-disabled="true" aria-label="Siguiente" class="is-disabled">&rsaquo;</span>
        @endif
    </nav>
@endif
