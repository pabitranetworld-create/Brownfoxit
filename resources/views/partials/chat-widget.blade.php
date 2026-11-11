{{-- Floating multi-channel chat button (Admin → চ্যাট বাটন) --}}
@php($chatChannels = setting('chat.enabled', true) ? \App\Support\ChatWidget::channels() : [])
@if ($chatChannels)
    @php($chatSide = setting('chat.position') === 'left' ? 'left' : 'right')
    @php($chatCta = trim((string) setting('chat.cta', '')))
    <div class="cw cw-{{ $chatSide }}" data-chat>
        @if (count($chatChannels) > 1)
            <ul class="cw-list" id="cwList" aria-label="Contact options">
                @foreach ($chatChannels as $i => $c)
                    <li style="--cw-c: {{ $c['color'] }}; --cw-i: {{ count($chatChannels) - $i }}">
                        <a href="{{ $c['url'] }}" @if ($c['external']) target="_blank" rel="noopener" @endif aria-label="{{ $c['label'] }}">
                            <span class="cw-tip">{{ $c['label'] }}</span><span class="cw-ic"><i class="{{ $c['icon'] }}"></i></span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <button type="button" class="cw-main" aria-expanded="false" aria-controls="cwList" aria-label="{{ $chatCta ?: 'Contact us' }}">
                <span class="cw-icons" aria-hidden="true">
                    @foreach ($chatChannels as $c)<i class="{{ $c['icon'] }}" style="--cw-c: {{ $c['color'] }}"></i>@endforeach
                    <i class="fa-solid fa-xmark cw-x"></i>
                </span>
            </button>
        @else
            @php($c = $chatChannels[0])
            <a class="cw-main single" href="{{ $c['url'] }}" @if ($c['external']) target="_blank" rel="noopener" @endif aria-label="{{ $chatCta ?: $c['label'] }}" style="--cw-c: {{ $c['color'] }}">
                <span class="cw-icons" aria-hidden="true"><i class="{{ $c['icon'] }}" style="--cw-c: {{ $c['color'] }}"></i></span>
            </a>
        @endif
        @if ($chatCta !== '')<span class="cw-cta">{{ $chatCta }}</span>@endif
    </div>
@endif
