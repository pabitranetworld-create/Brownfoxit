@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="adm-welcome">
        <div>
            <h2>স্বাগতম, {{ auth()->user()->name }} 👋</h2>
            <p class="adm-muted">এখান থেকে পুরো ওয়েবসাইটের লেখা, ছবি আর কন্টেন্ট বদলাতে পারবেন। সেভ করলেই সাইটে দেখা যাবে।</p>
        </div>
        <a href="{{ route('home') }}" target="_blank" class="adm-btn"><i class="fa-solid fa-eye"></i> সাইট দেখুন</a>
    </div>

    <div class="adm-stats">
        @foreach ($stats as $key => $s)
            <a href="{{ route('admin.resource.index', $key) }}" class="adm-stat">
                <i class="{{ $s['icon'] }}"></i>
                <strong>{{ $s['count'] }}</strong>
                <span>{{ $s['title'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="adm-card">
        <div class="adm-card-h"><h3><i class="fa-solid fa-house"></i> Home Page-এর অংশগুলো</h3></div>
        <div class="adm-tiles">
            @foreach ($sections as $key => $s)
                <a href="{{ route('admin.settings.edit', $key) }}" class="adm-tile"><i class="{{ $s['icon'] }}"></i> {{ $s['title'] }}</a>
            @endforeach
        </div>
    </div>

    <div class="adm-card">
        <div class="adm-card-h"><h3><i class="fa-solid fa-screwdriver-wrench"></i> টুলস</h3></div>
        <p class="adm-muted" style="margin-bottom:14px">সার্ভারের <code>.env</code> ফাইল হাতে বদলানোর পরে, অথবা কোনো পরিবর্তন সাইটে না দেখালে Cache পরিষ্কার করুন।</p>
        <form method="POST" action="{{ route('admin.clear-cache') }}">
            @csrf
            <button class="adm-btn ghost"><i class="fa-solid fa-broom"></i> Cache পরিষ্কার করুন</button>
        </form>
    </div>
@endsection
