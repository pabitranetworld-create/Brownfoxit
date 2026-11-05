@extends('layouts.app')

@php
    $tpl = $page->template ?: 'default';
    $pbTpl = $tpl;
    $pbScheme = $page->color_scheme ?: 'light';
    $topDark = \App\PageBuilder\Theme::topIsDark($page, $layout);
@endphp

@section('title', $page->seo_title ?: $page->title.' · '.setting('site.name'))
@section('meta_description', $page->seo_description ?: setting('site.meta_description'))
@section('body_class', 'page-inner tpl-'.$tpl.' theme-'.$pbScheme.' '.($topDark ? 'top-dark' : 'top-light'))

@push('head')
    @if ($page->noindex || $preview)<meta name="robots" content="noindex, nofollow">@endif
    @if ($page->og_image)<meta property="og:image" content="{{ media($page->og_image) }}">@endif
    @if (filled($page->custom_head)){!! $page->custom_head !!}@endif
    @if (filled($page->custom_css))<style>{!! $page->custom_css !!}</style>@endif
@endpush

@push('scripts')
    @if (filled($page->custom_js))<script>{!! $page->custom_js !!}</script>@endif
@endpush

@section('content')
    @if ($preview && empty($editor))
        <div class="pb-preview-bar">Preview — {{ $page->isPublished() ? 'এখনো প্রকাশ না করা পরিবর্তন' : 'Draft (এখনো প্রকাশিত নয়)' }}</div>
    @endif

    @if ($tpl === 'default' && $page->show_page_header)
        @include('pages.builder.page-header', ['title' => $page->title, 'subtitle' => $page->header_subtitle])
    @endif

    @include('pages.builder.layout', ['layout' => $layout, 'editor' => $editor ?? false])
@endsection
