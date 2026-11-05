@extends('admin.layout')

@section('title', $section['title'])

@section('content')
    <form method="POST" action="{{ route('admin.settings.update', $key) }}" enctype="multipart/form-data" class="adm-card" data-unsaved>
        @csrf
        @method('PUT')

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
@endsection
