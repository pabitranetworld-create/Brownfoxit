{{-- vars: $src, $alt, $effect, $class --}}
@php($alt = $alt ?: (\App\Models\Media::altFor($src) ?? ''))
<div class="pb-img fx-{{ $effect ?? 'none' }} {{ $class ?? '' }}" @if (($effect ?? '') === 'reveal' || ($effect ?? '') === 'mask') data-reveal @endif @if (($effect ?? '') === 'tilt') data-tilt @endif>
    <img src="{{ media($src) }}" alt="{{ $alt }}" loading="lazy" decoding="async">
</div>
