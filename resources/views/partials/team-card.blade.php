<article class="tm-card pb-item">
    <div class="tm-photo">
        @if ($m->photo)<img src="{{ media($m->photo) }}" alt="{{ $m->name }}{{ $m->role ? ', '.$m->role : '' }}" loading="lazy">@else<span class="tm-initials">{{ collect(explode(' ', $m->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>@endif
        @if ($socials = $m->socials())
            <div class="tm-social">@foreach ($socials as $sl)<a href="{{ $sl['url'] }}" target="_blank" rel="noopener" aria-label="{{ $m->name }} on {{ $sl['label'] }}"><i class="{{ $sl['icon'] }}"></i></a>@endforeach</div>
        @endif
    </div>
    <h3>{{ $m->name }}</h3>
    @if ($m->role)<p class="tm-role">{{ $m->role }}</p>@endif
    @if (! empty($showBio) && $m->bio)<p class="tm-bio">{{ $m->bio }}</p>@endif
</article>
