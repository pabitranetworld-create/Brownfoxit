@php
    $cols = array_values(array_filter([$d['col1'], $d['col2'], $d['col3']], 'filled'));
    $hl = (int) $d['highlight'];
    $cell = function ($v) {
        $t = strtolower(trim((string) $v));
        if (in_array($t, ['yes', 'y', '✓', 'true'], true)) return '<i class="fa-solid fa-circle-check yes" aria-label="Yes"></i>';
        if (in_array($t, ['no', 'n', '✗', 'false'], true)) return '<i class="fa-solid fa-circle-xmark no" aria-label="No"></i>';
        return e($v);
    };
@endphp
<div class="pb-compare-wrap">
    <table class="pb-compare">
        <thead><tr><th></th>@foreach ($cols as $ci => $c)<th @class(['hl' => $hl === $ci + 1])>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($d['rows'] as $row)
                <tr class="pb-item">
                    <th scope="row">{{ $row['label'] }}</th>
                    @foreach ($cols as $ci => $c)
                        <td @class(['hl' => $hl === $ci + 1])>{!! $cell($row['v'.($ci + 1)] ?? '') !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
