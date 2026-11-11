@php($team = \App\Models\TeamMember::forDisplay(true, 4))
@if ($team->isNotEmpty())
<section class="home-team" id="team">
    <div class="container">
        <div class="section-head">
            <p class="sub-title reveal">Our Professional Team</p>
            <h2 class="section-title text-fill">The people behind Brown Fox IT</h2>
        </div>
        <div class="tm-grid cols-4 tm-compact">
            @foreach ($team as $m)@include('partials.team-card', ['m' => $m])@endforeach
        </div>
        <div class="home-team-more reveal"><a href="{{ url('/about') }}#team" class="btn btn-gradient btn-xs">Meet the team <i class="fa-solid fa-arrow-right"></i></a></div>
    </div>
</section>
@endif
