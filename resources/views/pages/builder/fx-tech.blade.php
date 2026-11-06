{{-- Technology backgrounds for sections: circuit, matrix, hexgrid, scan, binary. --}}
@switch($fx)
    @case('circuit')
        <div class="pb-fx tf-circuit" aria-hidden="true">
            <svg viewBox="0 0 1200 600" preserveAspectRatio="xMidYMid slice">
                @php($paths = ['M0 80 H180 L220 120 H420', 'M0 200 H90 L130 160 H300 L340 200 H520', 'M1200 90 H1000 L960 130 H780', 'M1200 230 H1080 L1040 270 H860 L820 230 H700', 'M0 470 H160 L200 430 H380 L420 470 H560', 'M1200 500 H1020 L980 460 H820', 'M300 600 V520 L340 480 V400', 'M900 0 V90 L860 130 V200', 'M600 600 V540 L640 500 H760', 'M450 0 V60 L490 100 H620'])
                @foreach ($paths as $i => $p)
                    <path d="{{ $p }}" class="tf-trace"/>
                    <path d="{{ $p }}" class="tf-pulse q{{ $i % 4 }}" style="animation-delay: -{{ $i * .6 }}s; animation-duration: {{ 4 + ($i % 3) }}s"/>
                @endforeach
                @foreach ([[420, 80], [520, 200], [780, 90], [700, 230], [560, 470], [820, 500], [340, 400], [860, 200], [760, 500], [620, 100]] as $i => $c)<circle cx="{{ $c[0] }}" cy="{{ $c[1] }}" r="5" class="tf-pad q{{ $i % 4 }}"/>@endforeach
            </svg>
        </div>
    @break
    @case('matrix')
        <div class="pb-fx tf-matrix" aria-hidden="true"><canvas data-matrix></canvas></div>
    @break
    @case('hexgrid')
        <div class="pb-fx tf-hex" aria-hidden="true">@for ($i = 0; $i < 16; $i++)<i style="--x: {{ ($i * 37 + 5) % 100 }}%; --y: {{ ($i * 61 + 13) % 100 }}%; --d: {{ ($i * .7) % 6 }}s; --c: {{ $i % 4 }}"></i>@endfor</div>
    @break
    @case('scan')
        <div class="pb-fx tf-scan" aria-hidden="true"><div class="floor"></div><div class="line"></div></div>
    @break
    @case('binary')
        <div class="pb-fx tf-binary" aria-hidden="true">@for ($i = 0; $i < 18; $i++)<i style="--x: {{ ($i * 53 + 3) % 100 }}%; --t: {{ 9 + ($i % 6) * 2 }}s; --d: {{ ($i * 1.3) % 9 }}s; --c: {{ $i % 4 }}">@for ($k = 0; $k < 24; $k++){{ (($i * 7 + $k * 3) % 5) > 1 ? '1' : '0' }}<br>@endfor</i>@endfor</div>
    @break
@endswitch
