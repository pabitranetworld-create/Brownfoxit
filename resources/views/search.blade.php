@extends('layouts.app')

@section('title', ($q !== '' ? 'Search: '.$q : 'Search').' · '.setting('site.name'))
@section('body_class', 'page-inner tpl-default theme-dark top-dark')

@section('robots', 'noindex, follow')

@section('content')
    <section class="pb-page-header">
        <div class="pb-fx pb-fx-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="pb-fx pb-fx-grid" aria-hidden="true"></div>
        <div class="container">
            <h1 class="pb-page-title" data-words>Search</h1>
            <div style="margin-top:28px" data-anim="fade-up" data-anim-delay="200">@include('pages.widgets.search', ['d' => ['placeholder' => 'What are you looking for?', 'button' => 'Search']])</div>
        </div>
        <svg class="pb-wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0,40 C240,90 480,0 720,30 C960,60 1200,10 1440,40 L1440,80 L0,80 Z"/></svg>
    </section>

    <section class="pb-section sc-dark" style="--pt:20px;--pb:110px">
        <div class="pb-container">
            @if ($q === '')
                <p class="pb-results-meta">Type at least two letters to search pages and services.</p>
            @elseif ($results->isEmpty())
                <p class="pb-results-meta">No results for “{{ $q }}”. Try a shorter or different word, or <a href="{{ url('/contact') }}" style="color:#e8452f;text-decoration:underline">ask us directly</a>.</p>
            @else
                <p class="pb-results-meta">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('result', $results->count()) }} for “{{ $q }}”</p>
                <div class="pb-results" data-stagger="80">
                    @foreach ($results as $r)
                        @php($ex = e($r['excerpt']))
                        @foreach (array_filter(preg_split('/\s+/', $q), fn ($w) => mb_strlen($w) >= 3) as $w)
                            @php($ex = preg_replace('/('.preg_quote(e($w), '/').')/iu', '<mark>$1</mark>', $ex))
                        @endforeach
                        <a class="pb-result pb-item" href="{{ $r['url'] }}">
                            <small>{{ $r['type'] }}</small>
                            <h3>{{ $r['title'] }}</h3>
                            <p>{!! $ex !!}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
