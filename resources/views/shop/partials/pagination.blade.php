@if ($paginator->hasPages())
    <nav class="st-pagination" aria-label="Pages">
        @if ($paginator->onFirstPage())<span class="off"><i class="fa-solid fa-arrow-left"></i></span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="fa-solid fa-arrow-left"></i></a>@endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="off">…</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="on" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><i class="fa-solid fa-arrow-right"></i></a>@else<span class="off"><i class="fa-solid fa-arrow-right"></i></span>@endif
    </nav>
@endif
