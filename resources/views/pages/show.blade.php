@extends('layouts.app')

@section('title', $page->seo_title ?: $page->title.' · '.setting('site.name'))
@section('meta_description', $page->seo_description ?: setting('site.meta_description'))
@section('body_class', 'page-inner')

@push('head')
    @if ($page->noindex || $preview)<meta name="robots" content="noindex, nofollow">@endif
    @if ($page->og_image)<meta property="og:image" content="{{ media($page->og_image) }}">@endif
@endpush

@section('content')
    @if ($preview)
        <div class="pb-preview-bar">Preview — {{ $page->isPublished() ? 'এটা এখনো সাইটে প্রকাশ হয়নি এমন পরিবর্তন' : 'Draft (এখনো প্রকাশিত নয়)' }}</div>
    @endif

    @if ($page->show_page_header)
        @include('pages.builder.page-header', ['title' => $page->title, 'subtitle' => $page->header_subtitle])
    @endif

    @include('pages.builder.layout', ['layout' => $layout, 'editor' => $editor ?? false])
@endsection
