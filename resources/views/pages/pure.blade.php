{{--
    "Pure" Canvas page: the page only holds HTML widgets, so the visitor gets exactly the
    custom code — no theme CSS, no theme JS, no header/footer. If the code is a whole
    HTML document (<!DOCTYPE html> … </html>) it is sent as it is, with the page's
    custom head/CSS/JS added. In the builder preview small hooks make it clickable.
--}}
@php
    $editor = $editor ?? false;
    $widgets = [];
    $collect = function (array $list, $sectionId, $colId) use (&$collect, &$widgets) {
        foreach ($list as $w) {
            if (($w['type'] ?? '') === 'row') {
                foreach ($w['columns'] ?? [] as $c) { $collect($c['widgets'] ?? [], $sectionId, $colId); }
                continue;
            }
            $widgets[] = ['w' => $w, 'section' => $sectionId, 'col' => $colId];
        }
    };
    foreach ($layout['sections'] ?? [] as $s) {
        foreach ($s['columns'] ?? [] as $c) { $collect($c['widgets'] ?? [], $s['id'], $c['id']); }
    }
    $doc = count($widgets) === 1 && preg_match('/^\s*(<!--.*?-->\s*)*(<!doctype|<html)/is', (string) ($widgets[0]['w']['data']['code'] ?? ''));

    seo()->subject($page)->set(['title' => $page->title, 'robots' => $preview ? 'noindex, nofollow' : null]);
    $headExtra = '';
    if (filled($page->custom_head)) { $headExtra .= $page->custom_head; }
    if (filled($page->custom_css)) { $headExtra .= '<style>'.$page->custom_css.'</style>'; }
    $bodyExtra = filled($page->custom_js) ? '<script>'.$page->custom_js.'</script>' : '';
    if ($editor) {
        $bodyExtra .= '<script src="'.asset('assets/admin/preview-bridge.js').'?v='.filemtime(public_path('assets/admin/preview-bridge.js')).'"></script>';
    }
@endphp
@if ($doc)
@php
    $w = $widgets[0]['w'];
    $html = (string) $w['data']['code'];
    // SEO tags from the central system — only those the custom document does not already have.
    $S = seo()->resolve();
    $seoTags = '';
    if (! preg_match('#<title[\s>]#i', $html)) { $seoTags .= '<title>'.e($S['title']).'</title>'; }
    if (! preg_match('#<meta[^>]+name=["\']description#i', $html) && $S['description']) { $seoTags .= '<meta name="description" content="'.e($S['description']).'">'; }
    if (! preg_match('#<meta[^>]+name=["\']robots#i', $html)) { $seoTags .= '<meta name="robots" content="'.e($S['robots']).'">'; }
    if (! preg_match('#<link[^>]+rel=["\']canonical#i', $html)) { $seoTags .= '<link rel="canonical" href="'.e($S['canonical']).'">'; }
    if (! preg_match('#property=["\']og:title#i', $html)) { $seoTags .= '<meta property="og:title" content="'.e($S['og_title']).'"><meta property="og:url" content="'.e($S['canonical']).'"><meta property="og:type" content="website">'.($S['og_image'] ? '<meta property="og:image" content="'.e($S['og_image']).'">' : '').'<meta name="twitter:card" content="'.e($S['twitter_card']).'">'; }
    if (! str_contains($html, 'application/ld+json')) { $seoTags .= '<script type="application/ld+json">'.$S['jsonld'].'</script>'; }
    $headExtra = $seoTags.$headExtra;
    if (filled($w['data']['css'] ?? '')) { $headExtra .= '<style>'.$w['data']['css'].'</style>'; }
    $tail = (filled($w['data']['js'] ?? '') ? '<script>'.$w['data']['js'].'</script>' : '').$bodyExtra;
    if ($editor) {
        // the bridge needs to know which builder element the whole document belongs to
        $tail = '<script>document.documentElement.setAttribute("data-bfi-doc",'.json_encode($w['id']).');</script>'.$tail;
    }
    $html = preg_match('#</head>#i', $html) ? preg_replace('#</head>#i', $headExtra.'</head>', $html, 1) : $headExtra.$html;
    $html = preg_match('#</body>#i', $html) ? preg_replace('#</body>(?!.*</body>)#is', $tail.'</body>', $html, 1) : $html.$tail;
@endphp
{!! $html !!}
@else
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if ($editor) data-editor @endif>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.seo-head')
    <link rel="icon" href="{{ media(setting('site.favicon')) }}">
    <style>:where(body){margin:0}</style>
    {!! $headExtra !!}
</head>
<body class="pb-pure">
@if ($editor)
    <main>
    @foreach ($layout['sections'] ?? [] as $s)
        <div class="pb-section" data-bfi-id="{{ $s['id'] }}">
        @foreach ($s['columns'] ?? [] as $c)
            <div class="pb-col" data-bfi-id="{{ $c['id'] }}">
            @foreach ($widgets as $item)
                @if ($item['section'] === $s['id'] && $item['col'] === $c['id'])
                    <div class="pb-w pb-w-html" data-bfi-id="{{ $item['w']['id'] }}">@include('pages.widgets.html', ['d' => $item['w']['data'], 'wid' => $item['w']['id']])</div>
                @endif
            @endforeach
            </div>
        @endforeach
        </div>
    @endforeach
    </main>
@else
    @foreach ($widgets as $item)
        @include('pages.widgets.html', ['d' => $item['w']['data'], 'wid' => $item['w']['id']])
    @endforeach
@endif
{!! $bodyExtra !!}
</body>
</html>
@endif
