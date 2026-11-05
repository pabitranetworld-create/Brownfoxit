<div class="pb-tabs l-{{ $d['layout'] }}" data-tabs>
    <div class="pb-tab-list" role="tablist">
        @foreach ($d['items'] as $i => $item)
            <button type="button" role="tab" id="tab-{{ $wid }}-{{ $i }}" aria-controls="tp-{{ $wid }}-{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" class="pb-tab {{ $i === 0 ? 'on' : '' }}">
                @if ($item['icon'])<i class="{{ $item['icon'] }}"></i>@endif {{ $item['title'] }}
            </button>
        @endforeach
        <span class="pb-tab-ink" aria-hidden="true"></span>
    </div>
    <div class="pb-tab-panels">
        @foreach ($d['items'] as $i => $item)
            <div class="pb-tab-panel {{ $item['image'] ? 'has-img' : '' }}" role="tabpanel" id="tp-{{ $wid }}-{{ $i }}" aria-labelledby="tab-{{ $wid }}-{{ $i }}" @if ($i > 0) hidden @endif>
                <div class="pb-text">{!! $item['content'] !!}</div>
                @if ($item['image'])@include('pages.widgets._image', ['src' => $item['image'], 'alt' => $item['title'], 'effect' => 'zoom'])@endif
            </div>
        @endforeach
    </div>
</div>
