@php($pageTitle = 'My account')
@extends('layouts.store')
@section('title', 'My account · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-account">@include('account._nav')
        <div>
            <h2 class="st-h2">Hello, {{ auth()->user()->name }}</h2>
            <div class="st-stats" data-stagger="80">
                <a class="pb-item" href="{{ route('account.orders') }}"><i class="fa-solid fa-receipt"></i><strong>{{ auth()->user()->orders()->count() }}</strong><span>Orders</span></a>
                <a class="pb-item" href="{{ route('account.downloads') }}"><i class="fa-solid fa-download"></i><strong>{{ $downloadCount }}</strong><span>Downloads</span></a>
                <a class="pb-item" href="{{ route('shop.index') }}"><i class="fa-solid fa-store"></i><strong>Shop</strong><span>Browse products</span></a>
            </div>
            <h3 class="st-h3">Recent orders</h3>
            @if ($orders->isEmpty())<p class="st-muted">No orders yet.</p>@else @include('account._orders') @endif
        </div>
    </div>
@endsection
