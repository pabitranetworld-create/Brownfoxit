@php($pageTitle = 'My orders')
@extends('layouts.store')
@section('title', 'My orders · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-account">@include('account._nav')
        <div>@if ($orders->isEmpty())<div class="st-empty"><i class="fa-solid fa-receipt"></i><h2>No orders yet</h2><a class="st-btn" href="{{ route('shop.index') }}">Go to the shop</a></div>@else @include('account._orders') <div class="st-pages">{{ $orders->links('shop.partials.pagination') }}</div>@endif</div>
    </div>
@endsection
