@php($cards = setting('service_cards', []))
<section class="service-cards">
    <div class="container">
        <div class="section-head">
            <p class="sub-title reveal">{{ $cards['subtitle'] ?? '' }}</p>
            <h2 class="section-title text-fill">{{ $cards['title'] ?? '' }}</h2>
        </div>
        <div class="scard-grid">
            @foreach ($cards['items'] ?? [] as $card)
                <div class="scard reveal" data-delay="{{ $loop->index * 100 }}">
                    <div class="scard-img"><img src="{{ media($card['image']) }}" alt="{{ $card['title'] }}" loading="lazy"></div>
                    <a href="{{ link_to_url($card['url'] ?? '#') }}" class="btn btn-gradient btn-xs">View Portfolio</a>
                </div>
            @endforeach
        </div>
    </div>
</section>
