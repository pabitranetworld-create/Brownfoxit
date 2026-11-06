{{-- Neon running band with the services (two rows moving in opposite directions). --}}
@php($bandItems = $services->pluck('title')->filter()->values()->all() ?: ['Web Design & Development', 'Graphic Design', 'UI/UX Design', 'Video Editing', 'Digital Marketing'])
<section class="hh-band" aria-hidden="true">
    <div class="hh-band-row r1"><div class="hh-band-track">@for ($c = 0; $c < 4; $c++)@foreach ($bandItems as $b)<span>{{ $b }}</span><b>✦</b>@endforeach @endfor</div></div>
    <div class="hh-band-row r2"><div class="hh-band-track">@for ($c = 0; $c < 4; $c++)@foreach (array_reverse($bandItems) as $b)<span>{{ $b }}</span><b>●</b>@endforeach @endfor</div></div>
</section>
