<section class="counters hh-fx">
    @include('pages.builder.fx-tech', ['fx' => 'binary'])
    <div class="container counter-grid">
        @foreach (setting('counters', []) as $counter)
            <div class="counter-box reveal" data-delay="{{ $loop->index * 100 }}">
                <div class="cnum c-{{ $counter['color'] ?? 'blue' }}"><span class="count" data-to="{{ $counter['value'] }}">0</span>{{ $counter['suffix'] ?? '' }}</div>
                <p>{{ $counter['label'] }}</p>
            </div>
        @endforeach
    </div>
</section>
