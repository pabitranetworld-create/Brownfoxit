@php($section = setting('testimonials_section', []))
<section class="testimonials has-particles" data-number="6" data-size="5" data-random="true" data-colors='["#FF5337","#00A8FF","#9EC450","#FF5E5F"]'>
    <canvas class="particles"></canvas>
    <div class="container">
        <div class="section-head">
            <p class="sub-title reveal">{{ $section['subtitle'] ?? '' }}</p>
            <h2 class="section-title text-fill">{{ $section['title'] ?? '' }}</h2>
        </div>

        <div class="testi-stage">
            @foreach (array_slice($section['floating_avatars'] ?? [], 0, 6) as $avatar)
                <img class="testi-float tf{{ $loop->iteration }}" src="{{ media($avatar) }}" alt="" loading="lazy">
            @endforeach

            <div class="swiper testi-swiper">
                <div class="swiper-wrapper">
                    @foreach ($testimonials as $testimonial)
                        <div class="swiper-slide testi-item">
                            <div class="testi-avatar">
                                <img src="{{ media($testimonial->avatar) }}" alt="{{ $testimonial->name }}" loading="lazy">
                                <span class="quote"><i class="fa-solid fa-quote-right"></i></span>
                            </div>
                            <p>{{ $testimonial->content }}</p>
                            <h4>{{ $testimonial->name }}</h4>
                            <span>{{ $testimonial->designation }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="testi-nav">
                <button class="sw-arrow testi-prev" aria-label="Previous"><i class="fa-solid fa-arrow-left"></i></button>
                <button class="sw-arrow testi-next" aria-label="Next"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
</section>
