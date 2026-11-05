<div class="pb-faq" data-accordion>
    @foreach ($d['items'] as $i => $item)
        @php($open = $d['first_open'] && $i === 0)
        @if ($d['schema'])
            @php(\App\PageBuilder\Attr::$faq[] = ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($item['a'])]])
        @endif
        <div class="pb-item pb-faq-item {{ $open ? 'open' : '' }}">
            <button type="button" class="pb-faq-q" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="faq-{{ $wid }}-{{ $i }}">
                <span>{{ $item['q'] }}</span><i class="fa-solid fa-plus"></i>
            </button>
            <div class="pb-faq-a" id="faq-{{ $wid }}-{{ $i }}" role="region" @unless ($open) hidden @endunless>
                <div class="pb-text">{!! $item['a'] !!}</div>
            </div>
        </div>
    @endforeach
</div>
