@extends('admin.layout')

@section('title', ($item ? $def['singular'].' এডিট' : 'নতুন '.$def['singular']))

@section('content')
    <div class="adm-formhead">
        <a href="{{ route('admin.resource.index', $key) }}" class="adm-backlink"><i class="fa-solid fa-arrow-left"></i> {{ $def['title'] }}-এর তালিকা</a>
        @if ($item && ! empty($def['view_url']) && $item->slug)
            <a class="adm-btn ghost sm" href="{{ url(str_replace('{slug}', $item->slug, $def['view_url'])) }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> সাইটে দেখুন</a>
        @endif
        @if ($item && ! empty($def['duplicate']))
            <form method="POST" action="{{ route('admin.resource.duplicate', [$key, $item->id]) }}">@csrf<button class="adm-btn ghost sm"><i class="fa-regular fa-copy"></i> কপি করুন</button></form>
        @endif
    </div>

    <form method="POST" enctype="multipart/form-data" class="adm-card" data-unsaved
          action="{{ $item ? route('admin.resource.update', [$key, $item->id]) : route('admin.resource.store', $key) }}">
        @csrf
        @if ($item) @method('PUT') @endif

        <div class="adm-fields">
            @foreach ($def['fields'] as $field)
                @include('admin.fields.field', [
                    'field' => $field,
                    'name' => 'data['.$field['key'].']',
                    'file' => 'data_files['.$field['key'].']',
                    'value' => $values[$field['key']] ?? null,
                    'depth' => 0,
                    'newValue' => $values[$field['key'].'_new'] ?? null,
                ])
            @endforeach
        </div>

        <div class="adm-actions sticky">
            @if ($item)
                <button type="submit" form="delete-form" class="adm-btn danger ghost"><i class="fa-solid fa-trash"></i> মুছুন</button>
            @endif
            <span class="spacer"></span>
            <button class="adm-btn ghost" name="save_and_new" value="1"><i class="fa-solid fa-plus"></i> সেভ করে নতুন</button>
            <button class="adm-btn"><i class="fa-solid fa-floppy-disk"></i> সেভ করুন</button>
        </div>
    </form>

    @if ($item)
        <form id="delete-form" method="POST" action="{{ route('admin.resource.destroy', [$key, $item->id]) }}" data-confirm="সত্যিই মুছে ফেলবেন? এটা আর ফেরত আনা যাবে না।">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection
