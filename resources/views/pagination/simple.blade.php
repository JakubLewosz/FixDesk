@if($paginator->hasPages())
    <nav class="pagination" aria-label="Strony wyników">
        @if($paginator->onFirstPage())
            <span>Poprzednia</span>
        @else
            <a rel="prev" href="{{ $paginator->previousPageUrl() }}">← Poprzednia</a>
        @endif

        <span>Strona {{ $paginator->currentPage() }} z {{ $paginator->lastPage() }}</span>

        @if($paginator->hasMorePages())
            <a rel="next" href="{{ $paginator->nextPageUrl() }}">Następna →</a>
        @else
            <span>Następna</span>
        @endif
    </nav>
@endif
