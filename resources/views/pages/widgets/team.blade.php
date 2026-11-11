@php($items = \App\Models\TeamMember::forDisplay(false, (int) ($d['count'] ?? 0)))
@if ($items->isNotEmpty())
    <div class="tm-grid cols-{{ $d['columns'] ?? '4' }}">
        @foreach ($items as $m)@include('partials.team-card', ['m' => $m, 'showBio' => true])@endforeach
    </div>
@endif
