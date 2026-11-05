<div data-inview class="pb-flow sp-{{ $d['speed'] }} {{ $d['animated'] ? 'animated' : '' }}" data-flow role="img" aria-label="Workflow: {{ collect($d['nodes'])->pluck('label')->implode(' → ') }}">
    @foreach ($d['nodes'] as $i => $node)
        @if ($i > 0)
            <div class="pb-flow-link" aria-hidden="true"><span class="pb-flow-dot"></span><span class="pb-flow-dot d2"></span></div>
        @endif
        <div class="pb-item pb-flow-node c-{{ $node['color'] }}" style="--i:{{ $i }}">
            <span class="pb-flow-ic"><i class="{{ $node['icon'] ?: 'fa-solid fa-circle' }}"></i></span>
            <strong>{{ $node['label'] }}</strong>
            @if ($node['sub'])<small>{{ $node['sub'] }}</small>@endif
        </div>
    @endforeach
</div>
