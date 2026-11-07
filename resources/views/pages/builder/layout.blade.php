{{-- Renders a validated page-builder layout. $editor = true adds data-bfi-id for the admin preview. --}}
@php(\App\PageBuilder\Attr::$faq = [])
@foreach ($layout['sections'] ?? [] as $section)
    @include('pages.builder.section', ['section' => $section])
@endforeach

{{-- No FAQPage markup: Google shows FAQ rich results only for authoritative government/health sites; all structured data comes from App\Seo\StructuredData. --}}
