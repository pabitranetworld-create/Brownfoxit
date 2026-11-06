@php($pageTitle = 'My downloads')
@extends('layouts.store')
@section('title', 'My downloads · '.setting('site.name'))
@section('robots', 'noindex, nofollow')
@section('store')
    <div class="st-account">@include('account._nav')
        <div>
            @if ($downloads->isEmpty())
                <div class="st-empty"><i class="fa-solid fa-download"></i><h2>No downloads yet</h2><p>Files from paid orders appear here.</p></div>
            @else
                <ul class="st-dl">
                    @foreach ($downloads as $d)
                        <li><span><strong>{{ $d->file_name }}</strong><small>{{ $d->product?->name }} · order {{ $d->order?->number }} @if ($d->downloads_remaining !== null)· {{ $d->downloads_remaining }} left @endif @if ($d->expires_at)· until {{ $d->expires_at->format('d M Y') }}@endif</small></span>
                            @if ($p = $d->problem())<em>{{ $p }}</em>@else<a class="st-btn sm" href="{{ $d->link() }}"><i class="fa-solid fa-download"></i> Download</a>@endif</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
