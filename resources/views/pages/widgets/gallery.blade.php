<div class="pb-grid cols-{{ $d['columns'] }} pb-gallery" @if ($d['lightbox']) data-lightbox @endif>
    @foreach ($d['items'] as $item)
        @continue(blank($item['image']))
        <figure class="pb-item pb-gal-item" style="{{ $d['ratio'] !== 'auto' ? 'aspect-ratio:'.$d['ratio'] : '' }}">
            <a href="{{ media($item['image']) }}" @if (! $d['lightbox']) target="_blank" @endif data-caption="{{ $item['caption'] }}">
                <img src="{{ media($item['image']) }}" alt="{{ $item['caption'] ?: (\App\Models\Media::altFor($item['image']) ?? '') }}" loading="lazy">
                <span class="pb-gal-zoom"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
            </a>
            @if ($item['caption'])<figcaption>{{ $item['caption'] }}</figcaption>@endif
        </figure>
    @endforeach
</div>
