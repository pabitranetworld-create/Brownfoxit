{{-- One repeater row. Vars: $field, $name, $file, $rowKey, $row, $depth --}}
<div class="adm-row" data-row>
    <div class="adm-row-bar">
        <span class="adm-row-handle"><i class="fa-solid fa-grip-vertical"></i> <span data-row-title>{{ $row[$field['fields'][0]['key']] ?? 'নতুন' }}</span></span>
        <span class="adm-row-tools">
            <button type="button" data-row-up title="ওপরে"><i class="fa-solid fa-arrow-up"></i></button>
            <button type="button" data-row-down title="নিচে"><i class="fa-solid fa-arrow-down"></i></button>
            <button type="button" data-row-toggle title="খুলুন/বন্ধ"><i class="fa-solid fa-chevron-down"></i></button>
            <button type="button" data-row-remove class="danger" title="মুছুন"><i class="fa-solid fa-trash"></i></button>
        </span>
    </div>
    <div class="adm-row-body adm-fields">
        <input type="hidden" name="{{ $name }}[{{ $rowKey }}][_row]" value="1">
        @foreach ($field['fields'] as $sub)
            @include('admin.fields.field', [
                'field' => $sub,
                'name' => $name.'['.$rowKey.']['.$sub['key'].']',
                'file' => $file.'['.$rowKey.']['.$sub['key'].']',
                'value' => $row[$sub['key']] ?? ($sub['default'] ?? null),
                'depth' => $depth + 1,
            ])
        @endforeach
    </div>
</div>
