@php($cta = setting('cta', []))
<section class="cta" id="contact">
    <div class="container">
        <div class="cta-box hh-cta reveal">
            <div class="hh-cta-fx" aria-hidden="true">@include('pages.builder.fx-tech', ['fx' => 'scan'])</div>
            @if (! empty($cta['image']))
                <img loading="lazy" decoding="async" class="cta-person" src="{{ media($cta['image']) }}" alt="">
            @endif
            <div class="cta-content">
                <h2 class="text-fill cta-title">{{ $cta['title'] ?? '' }}</h2>
                <p>{{ $cta['text'] ?? '' }}</p>
                @if (! empty($cta['whatsapp_url']))
                    <a class="cta-btn wa" href="{{ $cta['whatsapp_url'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                @endif
                @if (! empty($cta['linkedin_url']))
                    <a class="cta-btn li" href="{{ $cta['linkedin_url'] }}" target="_blank" rel="noopener"><i class="fa-brands fa-linkedin"></i> LinkedIn</a>
                @endif
            </div>
        </div>
    </div>
</section>
