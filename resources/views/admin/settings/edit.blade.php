@extends('admin.layout')

@section('title', $section['title'])

@section('content')
    <form method="POST" action="{{ route('admin.settings.update', $key) }}" enctype="multipart/form-data" class="adm-card" data-unsaved>
        @csrf
        @method('PUT')

        @if (! empty($section['intro']))<div class="adm-alert warn"><i class="fa-solid fa-circle-info"></i> <div>{{ $section['intro'] }}</div></div>@endif
        <div class="adm-fields">
            @foreach ($fields as $field)
                @include('admin.fields.field', [
                    'field' => $field,
                    'name' => 'data['.$field['key'].']',
                    'file' => 'data_files['.$field['key'].']',
                    'value' => $values[$field['key']] ?? null,
                    'depth' => 0,
                ])
            @endforeach
        </div>

        <div class="adm-actions sticky">
            <a href="{{ route('home') }}" target="_blank" class="adm-btn ghost"><i class="fa-solid fa-eye"></i> সাইটে দেখুন</a>
            <button class="adm-btn"><i class="fa-solid fa-floppy-disk"></i> সেভ করুন</button>
        </div>
    </form>
    @if (! empty($section['mail_test']))
        <form method="POST" action="{{ route('admin.settings.mail-test') }}" class="adm-card" style="margin-top:18px">
            @csrf
            <p>সেভ করার পরে নিচের বাটনে চাপ দিয়ে দেখুন ইমেইল যাচ্ছে কিনা।</p>
            <button class="adm-btn ghost" style="margin-top:10px"><i class="fa-solid fa-paper-plane"></i> টেস্ট ইমেইল পাঠান</button>
        </form>
    @endif
@endsection
