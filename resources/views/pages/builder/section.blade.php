@php
    $s = $section['settings'];
    $style = ['--pt:'.(int) $s['pt'].'px', '--pb:'.(int) $s['pb'].'px'];
    if ($s['bg'] === 'color') { $style[] = '--sbg:'.$s['bg_color']; }
    if ($s['bg'] === 'image' && $s['bg_image']) { $style[] = '--sbg-img:url(\''.e(media($s['bg_image'])).'\')'; $style[] = '--ov:'.((int) $s['overlay'] / 100); }
    $classes = ['bg-'.$s['bg'], 'w-'.$s['width']];
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
    @if ($s['fx'] === 'orbs')
        <div class="pb-fx pb-fx-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
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
