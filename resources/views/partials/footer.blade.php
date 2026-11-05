@php($footer = setting('footer', []))
<footer class="site-footer has-particles" data-number="3" data-size="4" data-random="false" data-colors='["#FF7254","#95BE48","#4B35CB","#FF5337","#00A0FF"]'>
    <canvas class="particles"></canvas>

    <div class="container footer-grid">
        <div class="f-col f-about">
            <img class="f-logo" src="{{ media($footer['logo'] ?? setting('site.logo')) }}" alt="{{ setting('site.name') }}">
            <p>
                <strong>{{ $footer['about_title'] ?? '' }}</strong><br>
                {{ $footer['about_text'] ?? '' }}
            </p>
            <ul class="f-contact">
                @if (setting('site.phone'))
                    <li><i class="fa-brands fa-whatsapp"></i><a href="{{ setting('site.whatsapp_url', 'tel:'.setting('site.phone')) }}" target="_blank" rel="noopener">{{ setting('site.phone') }}</a></li>
                @endif
                @if (setting('site.email'))
                    <li><i class="fa-solid fa-envelope"></i><a href="mailto:{{ setting('site.email') }}">{{ setting('site.email') }}</a></li>
                @endif
                @if (setting('site.address'))
                    <li><i class="fa-solid fa-location-dot"></i><span>{{ setting('site.address') }}</span></li>
                @endif
            </ul>
        </div>

        <div class="f-col">
            <h5>Links</h5>
            <ul class="f-links">
                @foreach (array_filter($footer['links'] ?? [], fn ($l) => link_visible($l['url'] ?? null)) as $link)
                    <li><a href="{{ link_to_url($link['url']) }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="f-col">
            <h5>Support</h5>
            <ul class="f-links">
                @foreach (array_filter($footer['support'] ?? [], fn ($l) => link_visible($l['url'] ?? null)) as $link)
                    <li><a href="{{ link_to_url($link['url']) }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="f-col">
            <h5>Gallery</h5>
            <div class="f-gallery">
                @foreach ($footer['gallery'] ?? [] as $img)
                    <a href="{{ media($img['full'] ?? $img['thumb']) }}" target="_blank"><img src="{{ media($img['thumb']) }}" alt="" loading="lazy"></a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="container footer-bottom">
        <p>© {{ date('Y') }} {{ $footer['copyright_text'] ?? 'All rights reserved by' }} <a href="{{ route('home') }}">{{ $footer['copyright_link'] ?? request()->getHost() }}</a></p>
        @if (! empty($footer['payment_logos']))
            <img class="pay-logos" src="{{ media($footer['payment_logos']) }}" alt="Payment methods" loading="lazy">
        @endif
        <div class="follow">Follow us:
            @foreach (setting('site.social', []) as $social)
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}"><i class="{{ $social['icon'] }}"></i></a>
            @endforeach
        </div>
    </div>
</footer>
