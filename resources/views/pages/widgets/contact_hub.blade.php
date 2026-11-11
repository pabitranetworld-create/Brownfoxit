@php
    $wa = setting('site.whatsapp_url');
    $phone = setting('site.phone');
    $email = setting('site.email');
    $address = setting('site.address');
    $phoneLabel = $phone ? preg_replace('/^\+?880(\d{4})(\d{6})$/', '+880 $1 $2', preg_replace('/\s+/', '', $phone)) : null;
    $socials = array_filter((array) setting('site.social', []), fn ($x) => filled($x['url'] ?? null) && ! str_contains($x['icon'] ?? '', 'whatsapp'));
@endphp
<div class="ch">
    @if ($wa)
        <div class="ch-wa pb-item">
            <div class="ch-wa-glow" aria-hidden="true"></div>
            <div class="ch-wa-icon" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i><span class="ch-ping"></span></div>
            <div class="ch-wa-body">
                <span class="ch-badge"><i class="fa-solid fa-bolt"></i> Fastest reply</span>
                <h2>{{ $d['heading'] ?? '' }}</h2>
                @if (filled($d['text'] ?? ''))<p>{{ $d['text'] }}</p>@endif
                <div class="ch-wa-actions">
                    <a class="ch-wa-btn" href="{{ $wa }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> {{ $d['button'] ?: 'Chat on WhatsApp' }}</a>
                    @if ($phoneLabel)<span class="ch-wa-num">{{ $phoneLabel }}</span>@endif
                </div>
            </div>
        </div>
    @endif
    <div class="ch-cards">
        @if ($phone)<a class="ch-card pb-item" href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}"><span class="ch-ic c-blue"><i class="fa-solid fa-phone"></i></span><span><small>Call us</small><strong>{{ $phoneLabel }}</strong></span></a>@endif
        @if ($email)<a class="ch-card pb-item" href="mailto:{{ $email }}"><span class="ch-ic c-orange"><i class="fa-regular fa-envelope"></i></span><span><small>Email</small><strong>{{ $email }}</strong></span></a>@endif
        @if ($address)<div class="ch-card pb-item"><span class="ch-ic c-purple"><i class="fa-solid fa-location-dot"></i></span><span><small>Location</small><strong>{{ $address }}</strong><em>Working remotely with clients worldwide</em></span></div>@endif
        <a class="ch-card pb-item" href="#form"><span class="ch-ic c-green"><i class="fa-regular fa-paper-plane"></i></span><span><small>Contact form</small><strong>Send us a message</strong></span></a>
    </div>
    @if ($socials)
        <div class="ch-social pb-item"><span>Follow our work</span>@foreach ($socials as $sc)<a href="{{ $sc['url'] }}" target="_blank" rel="noopener" aria-label="{{ $sc['label'] ?? 'Profile' }}"><i class="{{ $sc['icon'] ?? 'fa-solid fa-link' }}"></i></a>@endforeach</div>
    @endif
</div>
