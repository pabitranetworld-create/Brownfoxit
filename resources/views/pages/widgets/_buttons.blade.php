@if (! empty($buttons))
    <div class="pb-btns {{ $size ?? '' }}">
        @foreach ($buttons as $b)
            @continue(blank($b['text']))
            <a href="{{ link_to_url($b['url'] ?: '#') }}" class="pb-btn pb-btn-{{ $b['style'] }}" @if ($b['new_tab']) target="_blank" rel="noopener" @endif>
                <span>{{ $b['text'] }}</span>@if ($b['icon'])<i class="{{ $b['icon'] }}"></i>@endif
            </a>
        @endforeach
    </div>
@endif
