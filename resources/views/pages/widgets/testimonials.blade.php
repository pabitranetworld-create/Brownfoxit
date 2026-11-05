@php($items = \App\Models\Testimonial::active()->ordered()->take((int) $d['count'])->get())
@if ($items->isNotEmpty())
    <div class="pb-testis st-{{ $d['style'] }} {{ $d['style'] === 'slider' ? 'swiper pb-swiper' : 'pb-grid cols-3' }}">
        <div class="{{ $d['style'] === 'slider' ? 'swiper-wrapper' : 'pb-contents' }}">
            @foreach ($items as $t)
                <figure class="pb-item pb-testi {{ $d['style'] === 'slider' ? 'swiper-slide' : '' }}">
                    <i class="fa-solid fa-quote-left pb-testi-q"></i>
                    <blockquote>{{ $t->content }}</blockquote>
                    <figcaption>
                        @if ($t->avatar)<img src="{{ media($t->avatar) }}" alt="{{ $t->name }}" loading="lazy">@endif
                        <span><strong>{{ $t->name }}</strong><small>{{ $t->designation }}</small></span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
        @if ($d['style'] === 'slider')<div class="swiper-pagination"></div>@endif
    </div>
@endif
