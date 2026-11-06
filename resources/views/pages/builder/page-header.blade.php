<section class="pb-page-header">
    <div class="pb-fx pb-fx-orbs" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="pb-fx pb-fx-grid" aria-hidden="true"></div>
    <div class="tb-glow" aria-hidden="true"><span></span><span></span><span></span></div>
    @include('pages.builder.fx-tech', ['fx' => 'circuit'])
    @php($phVisual = ($scheme ?? 'dark') === 'light' ? null : \App\PageBuilder\Visuals::guess($title, request()->path()))
    <div class="container {{ $phVisual ? 'ph-split' : '' }}">
        <div class="ph-text">
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
        @if ($phVisual)
            <div class="tv-wrap ph-visual" data-anim="zoom-in" data-anim-delay="200" aria-hidden="true">@include('pages.visuals.scene', ['scene' => $phVisual])</div>
        @endif
    </div>
    <svg class="pb-wave" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0,40 C240,90 480,0 720,30 C960,60 1200,10 1440,40 L1440,80 L0,80 Z"/></svg>
</section>
