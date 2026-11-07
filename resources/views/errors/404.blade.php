@extends('layouts.app')

@section('title', 'Page not found · '.setting('site.name'))
@section('body_class', 'page-inner tpl-default theme-dark top-dark')

@section('robots', 'noindex, follow')

@section('content')
    @include('pages.builder.page-header', ['title' => 'Page not found', 'subtitle' => 'The page you were looking for has moved or no longer exists.'])
    <section class="pb-section sc-dark" style="--pt:40px;--pb:110px">
        <div class="pb-container" style="max-width:720px;text-align:center">
            @include('pages.widgets.search', ['d' => ['placeholder' => 'Search the website…', 'button' => 'Search']])
            <p style="margin-top:26px"><a class="pb-btn pb-btn-primary" href="{{ route('home') }}">Back to the home page <i class="fa-solid fa-arrow-right"></i></a></p>
        </div>
    </section>
@endsection
