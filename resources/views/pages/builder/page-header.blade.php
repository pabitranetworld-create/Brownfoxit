<section class="pb-page-header">
    <div class="pb-fx pb-fx-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="pb-fx pb-fx-grid" aria-hidden="true"></div>
    <div class="vb-aurora-bg" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
    @include('pages.builder.fx-shapes')
    <div class="container">
        <nav class="pb-crumbs" aria-label="Breadcrumb" data-anim="fade" data-anim-dur="700">
            <a href="{{ route('home') }}">Home</a>
            @foreach ($crumbs ?? [] as $c)
                <i class="fa-solid fa-angle-right"></i><a href="{{ $c['url'] }}">{{ $c['label'] }}</a>
            @endforeach
            <i class="fa-solid fa-angle-right"></i><span>{{ $title }}</span>
        </nav>
        <h1 class="pb-page-title" data-words>{{ $title }}</h1>
        @if (! empty($subtitle))
            <p class="pb-page-sub" data-anim="fade-up" data-anim-delay="350">{{ $subtitle }}</p>
        @endif
    </div>
    <svg class="pb-wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0,40 C240,90 480,0 720,30 C960,60 1200,10 1440,40 L1440,80 L0,80 Z"/></svg>
</section>
