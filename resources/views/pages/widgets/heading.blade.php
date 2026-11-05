@php($tag = $d['tag'])
<div class="pb-heading">
    @if ($d['eyebrow'])<p class="pb-eyebrow">{{ $d['eyebrow'] }}</p>@endif
    <{{ $tag }} class="pb-h size-{{ $d['size'] }} c-{{ $d['color'] }} {{ $d['effect'] === 'fill' ? 'text-fill' : '' }} {{ $d['effect'] === 'sweep' ? 'fx-sweep' : '' }}" @if ($d['effect'] === 'words') data-words @endif>{{ $d['text'] }}</{{ $tag }}>
</div>
