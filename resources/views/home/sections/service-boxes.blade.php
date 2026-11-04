@if ($services->isNotEmpty())
<section class="service-boxes" id="services">
    <div class="container">
        <div class="sbox-wrap reveal">
            <button class="sw-arrow sbox-prev" aria-label="Previous"><i class="fa-solid fa-arrow-left"></i></button>
            <div class="swiper sbox-swiper">
                <div class="swiper-wrapper">
                    @foreach ($services as $service)
                        <div class="swiper-slide sbox">
                            <div class="sbox-icon ic-{{ $service->icon_color }}"><i class="{{ $service->icon }}"></i></div>
                            <h3>{{ $service->title }}</h3>
                            <p>{{ $service->short_description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <button class="sw-arrow sbox-next" aria-label="Next"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </div>
</section>
@endif
