{{-- Light inner page with a title banner. Used by shop, account, portfolio and blog pages. --}}
@extends('layouts.app')

@section('body_class', 'page-inner tpl-default theme-dark top-dark')


@section('content')
    @include('pages.builder.page-header', ['title' => $pageTitle ?? '', 'subtitle' => $pageSubtitle ?? null, 'crumbs' => $crumbs ?? []])
    @yield('after_header')
    <section class="pb-section sc-dark st-page" style="--pt:30px;--pb:110px">
        <div class="pb-container">
            @if (session('status'))<div class="st-alert ok" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>@endif
            @if ($errors->any())<div class="st-alert err" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <div>@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div></div>@endif
            @yield('store')
        </div>
    </section>
@endsection
