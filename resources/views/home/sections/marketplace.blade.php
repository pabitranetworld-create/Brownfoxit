@php($market = setting('marketplace', []))
<section class="marketplace has-particles" id="portfolio" data-number="4" data-size="4" data-random="false" data-colors='["#FF7155","#008BFF","#B0DA5E"]'>
    <canvas class="particles"></canvas>
    <div class="container">
        <h2 class="market-title reveal">{{ $market['title'] ?? '' }}</h2>
        <p class="market-sub reveal">{{ $market['subtitle'] ?? '' }}</p>
        <div class="market-logos">
            @foreach ($market['items'] ?? [] as $item)
                <a class="reveal" data-delay="{{ $loop->index * 100 }}" href="{{ $item['url'] ?? '#' }}" target="_blank" rel="noopener">
                    <img src="{{ media($item['image']) }}" alt="{{ $item['name'] }}" loading="lazy">
                </a>
            @endforeach
        </div>
    </div>
</section>
