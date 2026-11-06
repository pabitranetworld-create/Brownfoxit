@php($about = setting('about', []))
<section class="about has-particles" id="about" data-number="3" data-size="4" data-random="false" data-colors='["#FF5337","#B7DD64","#3B89FF","#FF9A3E"]'>
    <canvas class="particles"></canvas>
    <div class="container about-grid">
        <div class="about-media reveal" data-anim="fade-right">
            <img class="shape-spin about-shape3" src="{{ asset('uploads/2023/11/h10-shape3.png') }}" alt="">
            <img class="shape-slide about-shape4" src="{{ asset('uploads/2023/11/h10-shape4.png') }}" alt="">
            <div class="hh-orbit" aria-hidden="true"><i></i><i></i></div>
            <div class="about-photo">
                <img src="{{ media($about['image'] ?? '') }}" alt="{{ $about['subtitle'] ?? '' }}">
                @if (setting('site.youtube_video_id'))
                    <a class="play-btn play-fill" href="https://www.youtube.com/watch?v={{ setting('site.youtube_video_id') }}" data-video="{{ setting('site.youtube_video_id') }}" aria-label="Play video"><i class="fa-solid fa-play"></i></a>
                @endif
                <span class="learn-more">{{ $about['video_label'] ?? '' }}</span>
            </div>
            <img class="about-dots" src="{{ asset('uploads/2023/11/h10-shape2.png') }}" alt="">
        </div>

        <div class="about-content">
            <p class="sub-title reveal">{{ $about['subtitle'] ?? '' }}</p>
            <h2 class="section-title text-fill">{{ $about['title'] ?? '' }}</h2>
            @if (! empty($about['intro']))
                <p class="reveal">{!! $about['intro'] !!}</p>
            @endif
            @if (! empty($about['body']))
                <p class="reveal">{{ $about['body'] }}</p>
            @endif

            @if (! empty($about['partner_logo']))
                <p class="sync-label reveal">{{ $about['partner_label'] ?? '' }}</p>
                <img class="sync-logo reveal" src="{{ media($about['partner_logo']) }}" alt="{{ $about['partner_label'] ?? '' }}">
            @endif

            <div class="progress-list">
                @foreach ($about['skills'] ?? [] as $skill)
                    <div class="progress-item" data-value="{{ (int) $skill['value'] }}">
                        <div class="progress-head"><span>{{ $skill['label'] }}</span><span class="pval">0%</span></div>
                        <div class="progress-bar"><span class="bar g-{{ $skill['color'] ?? 'blue' }}"></span></div>
                    </div>
                @endforeach
            </div>

            <a href="{{ link_to_url($about['button_url'] ?? '/#contact') }}" class="btn btn-gradient reveal">{{ $about['button_text'] ?? 'Contact Us' }} <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>
