@php
    $items = array_values(array_filter($d['items'] ?? [], fn ($i) => trim((string) ($i['text'] ?? '')) !== ''));
    $rows = ($d['rows'] ?? '1') === '2' ? 2 : 1;
@endphp
@if ($items)
<div class="vb-marquee st-{{ $d['style'] ?? 'pills' }}" style="--mq-speed: {{ max(8, (int) ($d['speed'] ?? 35)) }}s">
    @for ($r = 0; $r < $rows; $r++)
        <div class="vb-mq-row {{ (($d['direction'] ?? 'left') === 'right') xor ($r === 1) ? 'to-right' : '' }}">
            {{-- the list is repeated so the loop is seamless; copies are hidden from screen readers --}}
            @for ($copy = 0; $copy < 2; $copy++)
                <ul class="vb-mq-track" @if ($copy) aria-hidden="true" @endif>
                    @foreach ($items as $i => $item)
                        <li class="vb-mq-item c-{{ $i % 6 }}">@if ($item['icon'] ?? '')<i class="{{ $item['icon'] }}"></i>@endif<span>{{ $item['text'] }}</span></li>
                    @endforeach
                </ul>
            @endfor
        </div>
    @endfor
</div>
@endif
