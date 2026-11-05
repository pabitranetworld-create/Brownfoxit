@if ($paginator->hasPages())
    <nav class="adm-pages">
        @if ($paginator->onFirstPage())
            <span class="off"><i class="fa-solid fa-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a>
        @endif
        <span class="info">পেজ {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a>
        @else
            <span class="off"><i class="fa-solid fa-chevron-right"></i></span>
        @endif
    </nav>
@endif
