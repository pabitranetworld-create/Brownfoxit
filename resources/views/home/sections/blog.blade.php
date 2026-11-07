@php($section = setting('blog_section', []))
<section class="blog has-particles" data-number="4" data-size="4" data-random="false" data-colors='["#FF635C","#008DFF","#7DAD32"]'>
    <canvas class="particles"></canvas>
    <div class="container">
        <div class="section-head">
            <p class="sub-title reveal">{{ $section['subtitle'] ?? '' }}</p>
            <h2 class="section-title text-fill">{{ $section['title'] ?? '' }}</h2>
        </div>

        @if ($posts->isNotEmpty())
            <div class="blog-wrap reveal">
                <button class="sw-arrow blog-prev" aria-label="Previous"><i class="fa-solid fa-arrow-left"></i></button>
                <div class="swiper blog-swiper">
                    <div class="swiper-wrapper">
                        @foreach ($posts as $post)
                            {{-- TODO (blog step): link to route('blog.show', $post) --}}
                            <div class="swiper-slide">
                                <article class="blog-card">
                                    <div class="blog-thumb">
                                        <img src="{{ media($post->image) }}" alt="{{ $post->title }}" loading="lazy">
                                        @if ($post->author)
                                            <div class="blog-author">
                                                <img loading="lazy" decoding="async" src="{{ media($post->author->avatar, asset('uploads/avatars/admin1.jpg')) }}" alt="">
                                                <span>by {{ $post->author->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="blog-body">
                                        <div class="blog-meta">
                                            @if ($post->category)<span class="cat">{{ $post->category->name }}</span>@endif
                                            <span>{{ $post->published_at?->format('F j, Y') }}</span>
                                        </div>
                                        <h4><a href="#">{{ $post->title }}</a></h4>
                                        <a href="#" class="blog-plus" aria-label="Read more">+</a>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                </div>
                <button class="sw-arrow blog-next" aria-label="Next"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
        @endif
    </div>
</section>
