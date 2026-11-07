@if ($clients->isNotEmpty())
<section class="clients">
    <div class="container clients-wrap">
        <button class="sw-arrow-plain client-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="swiper client-swiper">
            <div class="swiper-wrapper">
                {{-- Repeated so the endless logo slider always has enough slides --}}
                @foreach (range(1, max(2, (int) ceil(8 / $clients->count()))) as $loopRound)
                    @foreach ($clients as $client)
                        <div class="swiper-slide">
                            @if ($client->url)
                                <a href="{{ $client->url }}" target="_blank" rel="noopener"><img loading="lazy" decoding="async" src="{{ media($client->logo) }}" alt="{{ $client->name }}"></a>
                            @else
                                <img loading="lazy" decoding="async" src="{{ media($client->logo) }}" alt="{{ $client->name }}">
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
        <button class="sw-arrow-plain client-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
</section>
@endif
