@php
    $pageTitle = $post->title;
    $pageSubtitle = null;
    $crumbs = array_values(array_filter([['label' => 'Blog', 'url' => route('blog.index')], $post->category ? ['label' => $post->category->name, 'url' => route('blog.category', $post->category->slug)] : null]));
@endphp
@extends('layouts.store')
@section('title', ($post->seo_title ?: $post->title).' · '.setting('site.name'))
@section('meta_description', $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($post->excerpt ?: $post->body)), 155))
@push('head')
    @if ($post->image)<meta property="og:image" content="{{ media($post->image) }}">@endif
    <script type="application/ld+json">{!! json_encode(array_filter(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post->title, 'datePublished' => $post->published_at?->toIso8601String(), 'dateModified' => $post->updated_at?->toIso8601String(), 'image' => $post->image ? media($post->image) : null, 'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null, 'mainEntityOfPage' => $post->link()]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('store')
    <article class="st-article">
        <div class="st-article-meta" data-anim="fade-up">
            @if ($post->author)<span><i class="fa-regular fa-user"></i> {{ $post->author->name }}</span>@endif
            <span><i class="fa-regular fa-calendar"></i> {{ $post->published_at?->format('d M Y') }}</span>
            <span><i class="fa-regular fa-clock"></i> {{ $post->readingMinutes() }} min read</span>
        </div>
        @if ($post->image)<div class="st-case-hero pb-img" data-reveal data-anim="fade-up"><img src="{{ media($post->image) }}" alt="{{ $post->title }}"></div>@endif
        <div class="pb-text st-prose" data-anim="fade-up">{!! \App\PageBuilder\HtmlSanitizer::clean((string) $post->body) !!}</div>
        @if ($post->tags)<div class="st-tags">@foreach (array_filter(array_map('trim', explode(',', $post->tags))) as $t)<span>#{{ $t }}</span>@endforeach</div>@endif
        <div class="st-share">Share: <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($post->link()) }}" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><i class="fa-brands fa-linkedin"></i></a><a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->link()) }}" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="fa-brands fa-facebook"></i></a><a href="https://x.com/intent/post?url={{ urlencode($post->link()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" aria-label="Share on X"><i class="fa-brands fa-x-twitter"></i></a></div>
        <nav class="st-prevnext" aria-label="More articles">
            @if ($prev)<a href="{{ url('/blog/'.$prev->slug) }}"><small>Older</small><strong><i class="fa-solid fa-arrow-left"></i> {{ $prev->title }}</strong></a>@else<span></span>@endif
            @if ($next)<a href="{{ url('/blog/'.$next->slug) }}" class="r"><small>Newer</small><strong>{{ $next->title }} <i class="fa-solid fa-arrow-right"></i></strong></a>@endif
        </nav>
    </article>
    @if ($related->isNotEmpty())
        <section class="st-block"><h2 class="st-h2" data-anim="fade-up">Keep reading</h2><div class="pb-grid cols-3" data-stagger="80">@foreach ($related as $post)@include('blog._card')@endforeach</div></section>
    @endif
@endsection
