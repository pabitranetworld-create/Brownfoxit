@php($steps = setting('steps', []))
<section class="steps">
    <div class="container">
        <div class="section-head">
            <p class="sub-title reveal">{{ $steps['subtitle'] ?? '' }}</p>
            <h2 class="section-title text-fill">{{ $steps['title'] ?? '' }}</h2>
        </div>
        <div class="step-grid">
            @foreach ($steps['items'] ?? [] as $step)
                <div class="step reveal" data-delay="{{ $loop->index * 150 }}">
                    <div class="step-num n{{ ($loop->index % 4) + 1 }}">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                    @unless ($loop->last)
                        <img class="step-line" src="{{ asset('uploads/2023/12/dark-line-process.png') }}" alt="">
                    @endunless
                    @if (! empty($step['image']))
                        <div class="step-preview"><img src="{{ media($step['image']) }}" alt="" loading="lazy"></div>
                    @endif
                    <h4>{{ $step['title'] }}</h4>
                    <p>{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
