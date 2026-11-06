@php($hero = setting('hero', []))
<section class="hero has-particles" data-number="5" data-size="4" data-random="false" data-colors='["#FF6A59","#B3DB61","#0081FF","#FF5337"]'>
    <canvas class="particles"></canvas>
    @include('pages.builder.fx-tech', ['fx' => 'circuit'])
    <div class="bubbles" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>

    <div class="container hero-grid">
        <div class="hero-media reveal" data-anim="fade-right">
            {{-- Curved outline behind the photo, with two dots that travel along it --}}
            <svg class="hero-shape" viewBox="0 0 583 605" aria-hidden="true">
                <defs>
                    <linearGradient id="dotGrad1" x1="0%" x2="100%"><stop offset="0%" stop-color="#ff9a3e"/><stop offset="100%" stop-color="#ff5266"/></linearGradient>
                    <linearGradient id="dotGrad2" x1="0%" x2="100%"><stop offset="0%" stop-color="#00f0ff"/><stop offset="100%" stop-color="#004eff"/></linearGradient>
                </defs>
                <path id="heroShapePath" d="M14,184.994A272.662,272.662,0,0,1,86.538,74.96C132.162,33.11,221.1-17.907,297.3,11.94c28.578,11.194,42.619,28.879,65.679,54.017C409.646,116.831,454.6,150.057,501.2,201c43.357,47.4,67.034,79.645,68.733,122.975,1.5,38.358-25.27,102.87-107.945,170.117-42.679,34.716-214.147,160.718-340.157,83.027-23.3-14.366-71.984-79.632-99.988-174.055C-7.208,305.119,3.32,215.3,14,184.994Z" fill="none" stroke="rgba(180,198,246,0.29)" stroke-width="3"/>
                <circle r="11" fill="url(#dotGrad1)"><animateMotion dur="18s" repeatCount="indefinite" rotate="auto"><mpath href="#heroShapePath"/></animateMotion></circle>
                <circle r="11" fill="url(#dotGrad2)"><animateMotion dur="18s" begin="-9s" repeatCount="indefinite" rotate="auto"><mpath href="#heroShapePath"/></animateMotion></circle>
            </svg>

            @if (! empty($hero['badge_image']))
                <img class="hero-badge float-y" src="{{ media($hero['badge_image']) }}" alt="">
            @endif
 <div class="hh-ring" aria-hidden="true"><i></i><i></i><i></i></div>
            <span class="hh-chip c1" aria-hidden="true"><i class="fa-solid fa-code"></i></span>
            <span class="hh-chip c2" aria-hidden="true"><i class="fa-solid fa-robot"></i></span>
            <span class="hh-chip c3" aria-hidden="true"><i class="fa-solid fa-pen-nib"></i></span>
            <span class="hh-chip c4" aria-hidden="true"><i class="fa-solid fa-clapperboard"></i></span>
            <img class="hero-person" src="{{ media($hero['person_image'] ?? '') }}" alt="{{ $hero['title'] ?? '' }}">

            <div class="rating-pill">
                <div class="avatars">
                    @foreach ($hero['rating_avatars'] ?? [] as $avatar)
                        <img src="{{ media($avatar) }}" alt="">
                    @endforeach
                </div>
                <div class="rating-text">
                    <strong>{{ $hero['rating_title'] ?? '' }}</strong>
                    <span><i class="fa-solid fa-star"></i> {{ $hero['rating_value'] ?? '' }}</span>
                </div>
                <span class="rating-check"><i class="fa-solid fa-check"></i></span>
            </div>
        </div>

        <div class="hero-content">
            <p class="sub-title reveal" data-delay="100">{{ $hero['powered_by'] ?? '' }}</p>
            <h1 class="hero-title hh-shine reveal" data-delay="200">{{ $hero['title'] ?? setting('site.name') }}</h1>
            @php($skills = array_values(array_filter(array_map('trim', explode('|', (string) ($hero['subtitle'] ?? ''))))))
            @if (count($skills) > 1)
                {{-- the services type themselves one after another; the full list stays readable below as tags --}}
                <p class="hero-desc hh-typed reveal" data-delay="300"><span class="hh-prompt" aria-hidden="true">&gt;_</span> <span class="tx-rotate" data-rotate="{{ json_encode($skills, JSON_UNESCAPED_UNICODE) }}" aria-hidden="true">{{ $skills[0] }}</span><span class="sr-only">{{ $hero['subtitle'] }}</span></p>
                <ul class="hh-tags reveal" data-delay="350" aria-hidden="true">@foreach ($skills as $sk)<li style="--i: {{ $loop->index }}">{{ $sk }}</li>@endforeach</ul>
            @else
                <p class="hero-desc reveal" data-delay="300">{{ $hero['subtitle'] ?? '' }}</p>
            @endif
            <div class="hero-actions reveal" data-delay="400">
                @if (setting('site.youtube_video_id'))
                    <a class="play-btn" href="https://www.youtube.com/watch?v={{ setting('site.youtube_video_id') }}" data-video="{{ setting('site.youtube_video_id') }}" aria-label="Play video"><i class="fa-solid fa-play"></i></a>
                @endif
                <a href="{{ link_to_url($hero['button_url'] ?? '/#contact') }}" class="btn btn-gradient"><i class="fa-regular fa-comments"></i> {{ $hero['button_text'] ?? 'Contact Us' }}</a>
            </div>
        </div>
    </div>
</section>
