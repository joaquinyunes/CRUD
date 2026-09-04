@if ($paginator->hasPages())
    <nav class="r-pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="is-disabled">&lsaquo; Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Anterior</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente &rsaquo;</a>
        @else
            <span aria-disabled="true" class="is-disabled">Siguiente &rsaquo;</span>
        @endif
    </nav>
@endif
