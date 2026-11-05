@extends('admin.layout')

@section('title', 'প্রোফাইল ও পাসওয়ার্ড')

@section('content')
    <form method="POST" action="{{ route('admin.profile.update') }}" class="adm-card adm-narrow">
        @csrf
        @method('PUT')
        <div class="adm-grid">
            <div class="adm-field"><label>নাম</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="adm-field"><label>ইমেইল (লগইনের জন্য)</label><input name="email" type="email" value="{{ old('email', $user->email) }}" required></div>
        </div>

        <h3 class="adm-sub-h">পাসওয়ার্ড বদলান <small>(না বদলাতে চাইলে খালি রাখুন)</small></h3>
        <div class="adm-grid">
            <div class="adm-field full"><label>বর্তমান পাসওয়ার্ড</label><input name="current_password" type="password" autocomplete="current-password"></div>
            <div class="adm-field"><label>নতুন পাসওয়ার্ড</label><input name="password" type="password" autocomplete="new-password"></div>
            <div class="adm-field"><label>নতুন পাসওয়ার্ড আবার</label><input name="password_confirmation" type="password" autocomplete="new-password"></div>
        </div>

        <div class="adm-actions"><button class="adm-btn"><i class="fa-solid fa-floppy-disk"></i> সেভ করুন</button></div>
    </form>
@endsection
