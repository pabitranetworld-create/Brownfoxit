{{-- Renders a validated page-builder layout. $editor = true adds data-bfi-id for the admin preview. --}}
@php(\App\PageBuilder\Attr::$faq = [])
@foreach ($layout['sections'] ?? [] as $section)
    @include('pages.builder.section', ['section' => $section])
@endforeach

@if (! empty(\App\PageBuilder\Attr::$faq))
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => \App\PageBuilder\Attr::$faq], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
