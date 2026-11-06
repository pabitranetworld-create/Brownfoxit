@php
    $s = $section['settings'];
    $style = ['--pt:'.(int) $s['pt'].'px', '--pb:'.(int) $s['pb'].'px'];
    if ($s['bg'] === 'color') { $style[] = '--sbg:'.$s['bg_color']; }
    if ($s['bg'] === 'image' && $s['bg_image']) { $style[] = '--sbg-img:url(\''.e(media($s['bg_image'])).'\')'; $style[] = '--ov:'.((int) $s['overlay'] / 100); }
    $classes = ['bg-'.$s['bg'], 'w-'.\App\PageBuilder\Theme::sectionWidth($section, $pbTpl ?? 'default'), \App\PageBuilder\Theme::sectionIsDark($s, $pbScheme ?? 'dark') ? 'sc-dark' : 'sc-light'];
    if ($s['fx'] !== 'none') { $classes[] = 'has-fx'; }
    if ($s['divider_top']) { $classes[] = 'has-wave'; }
    if ($s['fx'] === 'particles') { $classes[] = 'has-particles'; }
    if ($s['height'] !== 'auto') { $classes[] = 'h-'.$s['height']; $classes[] = 'pos-'.$s['content_pos']; }
    if ($s['height'] === 'custom') { $style[] = '--mh:'.(int) $s['min_height'].'px'; }
@endphp
<section{!! \App\PageBuilder\Attr::element(array_merge($s, ['anim' => 'none', 'parallax' => 'none', 'anim_stagger' => 0]), 'pb-section', $editor ? $section['id'] : null, $classes, $style) !!} @if ($s['fx'] === 'particles') data-number="4" data-size="4" data-random="true" data-colors='["#FF6A59","#B3DB61","#0081FF","#FF5337"]' @endif>
    @if ($s['divider_top'])
        <svg class="pb-wave top" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0,40 C240,90 480,0 720,30 C960,60 1200,10 1440,40 L1440,0 L0,0 Z"/></svg>
    @endif
    @if ($s['bg'] === 'aurora')
        <div class="vb-aurora-bg" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    @endif
    @if ($s['fx'] === 'orbs')
        <div class="pb-fx pb-fx-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
    @elseif ($s['fx'] === 'aurora')
        <div class="pb-fx vb-aurora" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
    @elseif ($s['fx'] === 'shapes')
        @include('pages.builder.fx-shapes')
    @elseif ($s['fx'] === 'waves')
        <div class="pb-fx vb-waves" aria-hidden="true">@for ($i = 1; $i <= 3; $i++)<svg class="w{{ $i }}" viewBox="0 0 2880 120" preserveAspectRatio="none"><path d="M0,60 C240,110 480,10 720,60 C960,110 1200,10 1440,60 C1680,110 1920,10 2160,60 C2400,110 2640,10 2880,60 L2880,120 L0,120 Z"/></svg>@endfor</div>
    @elseif ($s['fx'] === 'beams')
        <div class="pb-fx vb-beams" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    @elseif ($s['fx'] === 'stars')
        <div class="pb-fx vb-stars" aria-hidden="true">@for ($i = 0; $i < 18; $i++)<i style="--x:{{ ($i * 37 + 11) % 100 }}%;--y:{{ ($i * 53 + 7) % 100 }}%;--d:{{ ($i % 6) * .55 }}s;--c:{{ $i % 5 }}"></i>@endfor</div>
    @elseif ($s['fx'] === 'confetti')
        <div class="pb-fx vb-confetti" aria-hidden="true">@for ($i = 0; $i < 22; $i++)<i style="--x:{{ ($i * 41 + 3) % 100 }}%;--d:{{ ($i * 0.7) % 9 }}s;--t:{{ 9 + ($i % 5) * 2 }}s;--c:{{ $i % 5 }};--r:{{ ($i * 47) % 360 }}deg"></i>@endfor</div>
    @elseif ($s['fx'] !== 'none')
        <div class="pb-fx pb-fx-{{ $s['fx'] }}" aria-hidden="true">@if ($s['fx'] === 'lines')<i></i><i></i><i></i><i></i><i></i>@endif</div>
    @endif
    @if ($s['fx'] === 'particles')
        <canvas class="particles" aria-hidden="true"></canvas>
    @endif
    <div class="pb-container">
        {{-- section animation is applied to the inner row so the background stays put --}}
        <div{!! \App\PageBuilder\Attr::element(['anim' => $s['anim'], 'anim_duration' => $s['anim_duration'], 'anim_delay' => $s['anim_delay'], 'anim_intensity' => $s['anim_intensity'], 'anim_mobile' => $s['anim_mobile'], 'anim_reduced' => $s['anim_reduced'], 'anim_stagger' => 0, 'parallax' => $s['parallax']], 'pb-row', null, ['gap-'.$s['gap'], 'va-'.$s['valign'], $s['reverse_mobile'] ? 'rev-m' : '']) !!}>
            @foreach ($section['columns'] as $column)
                @include('pages.builder.column', ['column' => $column])
            @endforeach
        </div>
    </div>
</section>
