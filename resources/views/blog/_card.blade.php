<article class="pb-item pb-post hv-lift">
    <a href="{{ $post->link() }}" class="pb-img fx-zoom" tabindex="-1">@if ($post->image)<img src="{{ media($post->image) }}" alt="{{ $post->title }}" loading="lazy">@else<span class="st-noimg"><i class="fa-regular fa-newspaper"></i></span>@endif</a>
    <div class="pb-post-body">
        <div class="pb-post-meta">@if ($post->category)<span>{{ $post->category->name }}</span>@endif {{ $post->published_at?->format('d M Y') }}</div>
        <h3><a href="{{ $post->link() }}">{{ $post->title }}</a></h3>
        @if ($post->excerpt)<p class="st-muted">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 120) }}</p>@endif
    </div>
</article>
