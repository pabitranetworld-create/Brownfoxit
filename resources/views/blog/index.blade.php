@php
    $pageTitle = $category ? $category->name : (setting('blog_page.title') ?: 'Blog & Insights');
    $pageSubtitle = $category ? null : (setting('blog_page.subtitle') ?: 'Articles on websites, software, automation, design and video.');
    $crumbs = $category ? [['label' => 'Blog', 'url' => route('blog.index')]] : [];
@endphp
@extends('layouts.store')
@section('title', ($q !== '' ? 'Search: '.$q.' · ' : '').$pageTitle.' · '.setting('site.name'))
@section('meta_description', setting('blog_page.meta_description') ?: ($pageSubtitle ?: $pageTitle))
@if ($q !== '')@section('robots', 'noindex, follow')@endif

@section('store')
    <div class="st-toolbar">
        <form method="get" class="st-search" role="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="{{ $q }}" placeholder="Search articles…" aria-label="Search articles"></form>
        @if ($categories->isNotEmpty())
            <div class="st-cats"><a href="{{ route('blog.index') }}" @class(['on' => ! $category])>All</a>@foreach ($categories as $c)<a href="{{ route('blog.category', $c->slug) }}" @class(['on' => $category?->id === $c->id])>{{ $c->name }} <small>{{ $c->posts_count }}</small></a>@endforeach</div>
        @endif
    </div>
    @if ($posts->isEmpty())
        <div class="st-empty"><i class="fa-regular fa-newspaper"></i><h2>{{ $q !== '' ? 'No articles match “'.$q.'”' : 'Articles are coming soon' }}</h2></div>
    @else
        <div class="pb-grid cols-3" data-stagger="80">@foreach ($posts as $post)@include('blog._card')@endforeach</div>
        <div class="st-pages">{{ $posts->links('shop.partials.pagination') }}</div>
    @endif
@endsection
