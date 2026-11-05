{{--
    One form field from App\Admin\Schema.
    Vars: $field, $name (input name), $file (file input name), $value, $depth (repeater nesting)
--}}
@php
    $type = $field['type'];
    $id = 'f_'.md5($name);
    $wide = in_array($type, ['repeater', 'images', 'html', 'textarea'], true) || ! empty($field['wide']);
@endphp

<div @class(['adm-field', 'full' => $wide, 'adm-field-'.$type])>
    @if ($type !== 'checkbox')
        <label for="{{ $id }}">{{ $field['label'] }} @if (! empty($field['required']))<b class="req">*</b>@endif</label>
    @endif

    @switch($type)
        @case('textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="3">{{ $value }}</textarea>
            @break

        @case('html')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="7" class="mono">{{ $value }}</textarea>
            @break

        @case('number')
            <input id="{{ $id }}" type="number" name="{{ $name }}" value="{{ $value }}" @isset($field['min']) min="{{ $field['min'] }}" @endisset @isset($field['max']) max="{{ $field['max'] }}" @endisset step="any">
            @break

        @case('url')
            <input id="{{ $id }}" type="text" inputmode="url" name="{{ $name }}" value="{{ $value }}" placeholder="https://">
            @break

        @case('email')
            <input id="{{ $id }}" type="email" name="{{ $name }}" value="{{ $value }}">
            @break

        @case('datetime')
            <input id="{{ $id }}" type="datetime-local" name="{{ $name }}" value="{{ $value }}">
            @break

        @case('icon')
            <div class="adm-icon-input">
                <span class="adm-ic"><i class="{{ $value }}" data-icon-preview></i></span>
                <input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" placeholder="fa-solid fa-star" data-icon-input>
            </div>
            @break

        @case('select')
            <select id="{{ $id }}" name="{{ $name }}">
                @foreach ($field['options'] as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>
            @break

        @case('model_select')
            <div class="adm-model-select">
                <select id="{{ $id }}" name="{{ $name }}">
                    <option value="">— বেছে নিন —</option>
                    @foreach ($field['model']::orderBy($field['display'])->get() as $opt)
                        <option value="{{ $opt->id }}" @selected((string) $value === (string) $opt->id)>{{ $opt->{$field['display']} }}</option>
                    @endforeach
                </select>
                @if (! empty($field['allow_new']))
                    <input name="{{ preg_replace('/\]$/', '_new]', $name) }}" value="{{ $newValue ?? '' }}" placeholder="অথবা নতুন একটা লিখুন">
                @endif
            </div>
            @break

        @case('checkbox')
            <input type="hidden" name="{{ $name }}" value="0">
            <label class="adm-switch">
                <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked($value ?? ($field['default'] ?? false))>
                <span></span> {{ $field['label'] }}
            </label>
            @break

        @case('image')
            <div class="adm-image" data-image>
                <div class="adm-image-prev">
                    <img src="{{ $value ? media($value) : '' }}" alt="" @if (! $value) hidden @endif data-image-img>
                    <i class="fa-regular fa-image" @if ($value) hidden @endif data-image-empty></i>
                </div>
                <div class="adm-image-ctl">
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-image-path>
                    <label class="adm-btn ghost sm"><i class="fa-solid fa-upload"></i> ছবি বেছে নিন
                        <input type="file" name="{{ $file }}" accept="image/*" hidden data-image-file>
                    </label>
                    <button type="button" class="adm-btn ghost sm" data-image-library><i class="fa-solid fa-photo-film"></i> লাইব্রেরি</button>
                    <button type="button" class="adm-btn ghost sm danger" data-image-clear @if (! $value) hidden @endif><i class="fa-solid fa-xmark"></i> সরান</button>
                    <small class="adm-path" data-image-name>{{ $value ? basename($value) : 'কোনো ছবি নেই' }}</small>
                </div>
            </div>
            @break

        @case('images')
            <div class="adm-images" data-images>
                @foreach ((array) $value as $img)
                    @if (filled($img))
                        <div class="adm-images-item">
                            <img src="{{ media($img) }}" alt="">
                            <input type="hidden" name="{{ $name }}[]" value="{{ $img }}">
                            <button type="button" data-images-remove title="সরান"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    @endif
                @endforeach
                <label class="adm-images-add" title="ছবি যোগ করুন">
                    <i class="fa-solid fa-plus"></i>
                    <input type="file" name="{{ $file }}[]" accept="image/*" multiple hidden data-images-file>
                </label>
            </div>
            @break

        @case('repeater')
            @php($placeholder = '__ROW'.$depth.'__')
            <div class="adm-repeater" data-repeater data-placeholder="{{ $placeholder }}">
                <div class="adm-rows" data-rows>
                    @foreach ((array) $value as $rowKey => $row)
                        @include('admin.fields.row', ['field' => $field, 'name' => $name, 'file' => $file, 'rowKey' => $rowKey, 'row' => (array) $row, 'depth' => $depth])
                    @endforeach
                </div>
                <template data-row-template>
                    @include('admin.fields.row', ['field' => $field, 'name' => $name, 'file' => $file, 'rowKey' => $placeholder, 'row' => [], 'depth' => $depth])
                </template>
                <button type="button" class="adm-btn ghost sm" data-row-add><i class="fa-solid fa-plus"></i> নতুন যোগ করুন</button>
            </div>
            @break

        @default
            <input id="{{ $id }}" type="text" name="{{ $name }}" value="{{ $value }}">
    @endswitch

    @if (! empty($field['help']))
        <small class="adm-help">{{ $field['help'] }}</small>
    @endif
</div>
