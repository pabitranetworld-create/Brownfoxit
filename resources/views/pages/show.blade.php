@extends('layouts.app')

@php
    $tpl = $page->template ?: 'default';
    $pbTpl = $tpl;
    $pbScheme = $page->color_scheme ?: 'light';
    $topDark = \App\PageBuilder\Theme::topIsDark($page, $layout);
@endphp

@php(seo()->subject($page))
@section('title', $page->title)
@if ($preview)@section('robots', 'noindex, nofollow')@endif
@section('body_class', 'page-inner tpl-'.$tpl.' theme-'.$pbScheme.' '.($topDark ? 'top-dark' : 'top-light'))

@push('head')
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
        @include('pages.builder.page-header', ['title' => $page->title, 'subtitle' => $page->header_subtitle, 'scheme' => $pbScheme])
    @endif

    @if ($tpl === 'full_width' && str_contains($page->slug, '/') && empty($editor))
        @php($bc = seo()->resolve()['breadcrumbs'])
        @if (count($bc) > 1)
            <nav class="pb-crumbbar" aria-label="Breadcrumb"><ol>
                @foreach ($bc as $i => [$name, $href])
                    <li>@if ($loop->last)<span aria-current="page">{{ $name }}</span>@else<a href="{{ $href }}">{{ $name }}</a>@endif</li>
                @endforeach
            </ol></nav>
        @endif
    @endif

    @include('pages.builder.layout', ['layout' => $layout, 'editor' => $editor ?? false])
@endsection
