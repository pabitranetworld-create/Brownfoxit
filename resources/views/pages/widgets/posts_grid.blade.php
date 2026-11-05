@php($items = \App\Models\Post::published()->with('category')->latest('published_at')->take((int) $d['count'])->get())
<div class="pb-grid cols-{{ $d['columns'] }} pb-posts">
    @foreach ($items as $p)
        <article class="pb-item pb-post hv-lift">
            <div class="pb-img fx-zoom"><img src="{{ media($p->image) }}" alt="{{ $p->title }}" loading="lazy"></div>
            <div class="pb-post-body">
                <p class="pb-post-meta">@if ($p->category)<span>{{ $p->category->name }}</span>@endif {{ $p->published_at?->format('M j, Y') }}</p>
                <h3>{{ $p->title }}</h3>
            </div>
        </article>
    @endforeach
</div>
