<?php
/**
 * End-to-end test of the CMS: page builder, every widget, motion settings,
 * sanitising, drafts/publish, revisions, duplicate/templates and the media library.
 *
 *   php tests/builder-e2e.php http://127.0.0.1:8000 admin@example.com 'Secret@12345'
 */

[$script, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8000', 'admin@example.com', 'Secret@12345'];
require __DIR__.'/lib.php';
$slug = 'e2e-page-'.substr(bin2hex(random_bytes(4)), 0, 6);

function login(string $email, string $password): void
{
    $p = get('/admin/login');
    if ($p['code'] === 302) {
        return; // already logged in
    }
    $f = form($p['body'], '/admin/login');
    set($f['fields'], 'email', $email);
    set($f['fields'], 'password', $password);
    $r = submit($f['action'], $f['fields']);
    ok($r['code'] === 302, 'logged in');
}

function json_post(string $url, array $fields, string $csrf): array
{
    $flat = [];
    foreach ($fields as $k => $v) {
        if (is_array($v)) {
            foreach ($v as $k2 => $v2) {
                $flat[] = ["{$k}[{$k2}]", is_bool($v2) ? ($v2 ? '1' : '0') : (string) $v2];
            }
        } else {
            $flat[] = [$k, (string) $v];
        }
    }
    $boundary = '----bfi'.bin2hex(random_bytes(6));
    $body = '';
    foreach ($flat as [$n, $v]) {
        $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$n}\"\r\n\r\n{$v}\r\n";
    }
    $body .= "--{$boundary}--\r\n";
    $r = http('POST', $url, $body, ["Content-Type: multipart/form-data; boundary={$boundary}", 'Accept: application/json', 'X-CSRF-TOKEN: '.$csrf, 'X-Requested-With: XMLHttpRequest']);
    $r['json'] = json_decode($r['body'], true);

    return $r;
}

function widget(string $type, array $data = [], array $settings = [], array $columns = []): array
{
    $w = ['id' => substr(md5($type.microtime()), 0, 10), 'type' => $type, 'data' => $data, 'settings' => $settings];
    if ($columns) {
        $w['columns'] = $columns;
    }

    return $w;
}

echo "== CMS: pages + builder\n";
login($email, $password);
ok_page(get('/admin/pages'), 'pages list');
ok_page(get('/admin/media'), 'media library');

$list = get('/admin/pages');
$f = form($list['body'], '/admin/pages');
set($f['fields'], 'title', 'E2E Builder Page');
set($f['fields'], 'slug', $slug);
$r = submit($f['action'], $f['fields']);
ok($r['code'] === 302 && preg_match('#/admin/pages/(\d+)/builder#', (string) $r['location'], $m) === 1, 'page created → builder');
$id = (int) ($m[1] ?? 0);

$builder = get("/admin/pages/{$id}/builder");
ok_page($builder, 'builder opens');
preg_match('#csrf: "([^"]+)"#', $builder['body'], $cm);
$csrf = stripcslashes($cm[1] ?? '');
ok(str_contains($builder['body'], 'BFI_BUILDER') && $csrf !== '', 'builder has data + token');
preg_match('#window\.BFI_BUILDER = \{\s*defs: (\{.*?\}),\s*layout:#s', $builder['body'], $dm);
$defs = json_decode($dm[1] ?? '', true);
ok(is_array($defs) && count($defs['widgets'] ?? []) >= 20, 'builder knows '.count($defs['widgets'] ?? []).' widgets');

// One page with every widget type
$motion = ['anim' => 'zoom-in', 'anim_duration' => 1200, 'anim_delay' => 150, 'anim_stagger' => 150, 'anim_intensity' => 'bold', 'hover' => 'lift', 'parallax' => 'medium', 'anim_mobile' => 'simple', 'anim_reduced' => 'fade', 'hide_mobile' => true];
$widgets = [
    widget('hero', ['eyebrow' => 'E2E eyebrow', 'title' => 'Automate the boring work', 'highlight' => 'boring work', 'text' => 'Hero text E2E', 'buttons' => [['text' => 'Start now', 'url' => '/contact', 'style' => 'primary', 'icon' => 'fa-solid fa-arrow-right', 'new_tab' => false]], 'image' => 'uploads/2026/03/Arjun-Kumar-Pabitra-Pabitra-Sarkar-12-222.png', 'layout' => 'split', 'title_effect' => 'words', 'image_effect' => 'float']),
    widget('heading', ['eyebrow' => 'Eyebrow E2E', 'text' => 'Heading E2E', 'tag' => 'h2', 'size' => 'xl', 'color' => 'gradient', 'effect' => 'words'], $motion),
    widget('text', ['content' => '<p>Safe <strong>text</strong> E2E<script>alert(1)</script><a href="javascript:alert(2)" onclick="x()">bad link</a> <a href="https://example.com" target="_blank">good link</a></p><img src=x onerror=alert(3)>']),
    widget('image', ['image' => 'uploads/2023/12/dark-client1.png', 'alt' => 'Client logo E2E', 'caption' => 'Caption E2E', 'effect' => 'reveal']),
    widget('buttons', ['buttons' => [['text' => 'Button E2E', 'url' => 'javascript:alert(1)', 'style' => 'ghost', 'icon' => '', 'new_tab' => true]]]),
    widget('icon_boxes', ['items' => [['icon' => 'fa-solid fa-robot', 'title' => 'IconBox E2E', 'text' => 'Box text', 'url' => '/services']], 'columns' => '3'], ['anim' => 'fade-up', 'anim_stagger' => 120]),
    widget('list', ['items' => [['text' => 'List item E2E']], 'style' => 'check']),
    widget('video', ['url' => 'https://www.youtube.com/watch?v=SF4aHwxHtZ0', 'title' => 'Video E2E', 'mode' => 'popup']),
    widget('gallery', ['items' => [['image' => 'uploads/2023/10/g-img-7-300x300.jpg', 'caption' => 'Gallery E2E']]]),
    widget('faq', ['items' => [['q' => 'FAQ question E2E?', 'a' => '<p>FAQ answer E2E</p>']], 'schema' => true]),
    widget('tabs', ['items' => [['title' => 'Tab E2E', 'icon' => '', 'content' => '<p>Tab body E2E</p>', 'image' => '']]]),
    widget('cta', ['title' => 'CTA E2E', 'text' => 'CTA text']),
    widget('counters', ['items' => [['value' => 42, 'suffix' => '+', 'label' => 'Counter E2E']]]),
    widget('steps', ['items' => [['icon' => '', 'title' => 'Step E2E', 'text' => 'Step text']]]),
    widget('workflow', ['nodes' => [['icon' => 'fa-solid fa-bolt', 'label' => 'Webhook E2E', 'sub' => 'n8n', 'color' => 'orange'], ['icon' => 'fa-solid fa-robot', 'label' => 'AI E2E', 'sub' => '', 'color' => 'purple']]]),
    widget('comparison', ['col1' => 'Manual E2E', 'col2' => 'Automated E2E', 'rows' => [['label' => 'Row E2E', 'v1' => 'no', 'v2' => 'yes', 'v3' => '']]]),
    widget('testimonials', ['count' => 3, 'style' => 'grid']),
    widget('services_grid', ['count' => 2]),
    widget('projects_grid', ['count' => 0, 'filter' => true]),
    widget('posts_grid', ['count' => 2]),
    widget('divider', ['style' => 'glow', 'height' => 30]),
    widget('html', ['code' => '<div class="custom-html-e2e">Custom HTML E2E</div>']),
    widget('row', ['gap' => 'm', 'valign' => 'center'], [], [
        ['id' => 'nestedcol1', 'settings' => ['w' => 50], 'widgets' => [widget('heading', ['text' => 'Nested heading E2E'])]],
        ['id' => 'nestedcol2', 'settings' => ['w' => 50], 'widgets' => [widget('row', [], [], [])]], // nested row in nested row: must be dropped
    ]),
    ['id' => 'bad1', 'type' => 'evil_widget', 'data' => []], // unknown type: must be dropped
];
$layout = ['version' => 1, 'sections' => [
    ['id' => 'sectionone', 'settings' => ['bg' => 'gradient', 'fx' => 'orbs', 'pt' => 120, 'width' => 'boxed'], 'columns' => [
        ['id' => 'columnone', 'settings' => ['w' => 100], 'widgets' => $widgets],
    ]],
    ['id' => 'sectiontwo', 'settings' => ['fx' => 'lines', 'divider_top' => true], 'columns' => [
        ['id' => 'colleft', 'settings' => ['w' => 60, 'box' => 'glow'], 'widgets' => [widget('heading', ['text' => 'Left column E2E'])]],
        ['id' => 'colright', 'settings' => ['w' => 40], 'widgets' => []],
    ]],
]];
$pageFields = ['title' => 'E2E Builder Page', 'slug' => $slug, 'seo_title' => 'SEO title E2E', 'seo_description' => 'SEO description E2E', 'show_page_header' => true, 'header_subtitle' => 'Subtitle E2E', 'noindex' => false, 'og_image' => ''];

$r = json_post("/admin/pages/{$id}/save", ['layout' => json_encode($layout), 'page' => $pageFields], $csrf);
ok(($r['json']['ok'] ?? false) === true, 'draft saved');
$saved = $r['json']['layout'] ?? [];
$flatTypes = [];
array_walk_recursive($saved, function ($v, $k) use (&$flatTypes) { if ($k === 'type') { $flatTypes[] = $v; } });
ok(! in_array('evil_widget', $flatTypes, true), 'unknown widget type removed');
$savedJson = json_encode($saved);
ok(! str_contains($savedJson, '<script') && ! str_contains($savedJson, 'onerror') && ! str_contains($savedJson, 'onclick') && ! str_contains($savedJson, 'javascript:'), 'scripts, event handlers and javascript: links removed');
ok(str_contains($savedJson, 'https:\/\/example.com'), 'safe link kept');
$rowWidgets = $saved['sections'][0]['columns'][0]['widgets'][22]['columns'][1]['widgets'] ?? null;
ok($rowWidgets === [], 'row inside a nested row is not allowed');
ok(count(array_unique(array_filter($flatTypes))) >= 22, 'all '.count(array_unique($flatTypes)).' widget types kept');

ok(get('/'.$slug)['code'] === 404, 'draft page is not public');

$pv = json_post("/admin/pages/{$id}/preview", ['layout' => json_encode($saved), 'editor' => '1', 'page' => ['title' => 'E2E Builder Page', 'show_page_header' => true, 'header_subtitle' => '']], $csrf);
ok($pv['code'] === 200 && str_contains($pv['body'], 'data-bfi-id="sectionone"') && str_contains($pv['body'], 'data-editor'), 'live preview renders with editor hooks');

$r = json_post("/admin/pages/{$id}/publish", ['page' => $pageFields], $csrf);
ok(($r['json']['ok'] ?? false) && ($r['json']['status'] ?? '') === 'published', 'published');

$pub = get('/'.$slug);
ok_page($pub, 'public page loads');
$html = $pub['body'];
foreach (['Automate the', 'grad-text', 'E2E eyebrow', 'Heading E2E', 'Safe <strong>text</strong> E2E', 'good link', 'Client logo E2E', 'Caption E2E', 'Button E2E', 'IconBox E2E', 'List item E2E', 'data-video-popup', 'Gallery E2E', 'FAQ question E2E?', 'Tab E2E', 'CTA E2E', 'Counter E2E', 'data-to="42"', 'Step E2E', 'Webhook E2E', 'Automated E2E', 'fa-circle-check yes', 'custom-html-e2e', 'Nested heading E2E', 'Left column E2E', 'SEO title E2E', 'SEO description E2E', 'Subtitle E2E', 'Web Design &amp; Development', 'Premium Real Estate', 'pb-post-body', 'Elizabeth K.', 'pb-fx-orbs', 'pb-fx-lines', 'box-glow', 'has-wave', '"@type":"FAQPage"', 'motion.js', 'pages.css', 'data-filter="web-design"'] as $needle) {
    ok(str_contains($html, $needle), 'renders: '.$needle);
}
ok(! str_contains($html, 'alert(') && ! str_contains($html, 'evil_widget'), 'no injected script on the public page');
ok(! str_contains($html, 'data-bfi-id'), 'no editor hooks on the public page');
// motion settings reach the HTML
foreach (['data-anim="zoom-in"', 'data-anim-dur="1200"', 'data-anim-delay="150"', 'data-stagger="150"', 'data-anim-int="bold"', 'hv-lift', 'data-parallax="medium"', 'data-anim-mobile="simple"', 'data-anim-reduced="fade"', 'hide-m', 'data-words'] as $needle) {
    ok(str_contains($html, $needle), 'motion setting in HTML: '.$needle);
}

echo "== CMS: drafts, revisions, restore\n";
$draft = $saved;
$draft['sections'][0]['columns'][0]['widgets'][1]['data']['text'] = 'Changed heading DRAFT';
$r = json_post("/admin/pages/{$id}/save", ['layout' => json_encode($draft), 'page' => $pageFields], $csrf);
ok($r['json']['ok'] ?? false, 'second draft saved');
ok(! str_contains(get('/'.$slug)['body'], 'Changed heading DRAFT'), 'live page unchanged by draft');
$pv = get("/admin/pages/{$id}/preview");
ok(str_contains($pv['body'], 'Changed heading DRAFT'), 'preview shows the draft');
$pagesList = get('/admin/pages')['body'];
ok(str_contains($pagesList, 'অপ্রকাশিত পরিবর্তন'), 'list shows unpublished changes');
$revs = json_decode(get("/admin/pages/{$id}/revisions")['body'], true);
ok(is_array($revs) && count($revs) >= 3, 'revisions recorded ('.count($revs ?? []).')');
$publishedRev = array_values(array_filter($revs ?? [], fn ($r) => $r['note'] === 'published'))[0] ?? null;
$r = json_post("/admin/pages/{$id}/revisions/".($publishedRev['id'] ?? 0).'/restore', [], $csrf);
ok(($r['json']['ok'] ?? false) && str_contains(json_encode($r['json']['layout'] ?? []), 'Heading E2E'), 'restore published version into draft');

echo "== CMS: slugs, duplicate, templates\n";
$r = json_post("/admin/pages/{$id}/save", ['layout' => json_encode($saved), 'page' => array_merge($pageFields, ['slug' => 'admin'])], $csrf);
ok($r['code'] === 422, 'reserved slug "admin" refused');
$r = submit("/admin/pages/{$id}/duplicate", [['_token', $csrf]]);
ok($r['code'] === 302 && str_contains((string) $r['location'], '/builder'), 'page duplicated');
$r = submit("/admin/pages/{$id}/duplicate", [['_token', $csrf], ['as_template', '1']]);
ok($r['code'] === 302, 'saved as template');
$tpls = get('/admin/pages?show=templates')['body'];
ok(str_contains($tpls, 'E2E Builder Page (Template)'), 'template listed');
preg_match('#<option value="(\d+)">Template: E2E Builder Page \(Template\)#', get('/admin/pages')['body'], $tm);
$f = form(get('/admin/pages')['body'], '/admin/pages');
set($f['fields'], 'title', 'From Template E2E');
set($f['fields'], 'template_id', $tm[1] ?? '');
$r = submit($f['action'], $f['fields']);
preg_match('#/admin/pages/(\d+)/builder#', (string) $r['location'], $nm);
$nb = get('/admin/pages/'.($nm[1] ?? 0).'/builder')['body'];
ok(str_contains($nb, 'Webhook E2E'), 'new page starts from template layout');
ok(get('/'.$slug.'-copy')['code'] === 404, 'duplicate stays draft');

$r = submit("/admin/pages/{$id}/unpublish", [['_token', $csrf]]);
ok($r['code'] === 302 && get('/'.$slug)['code'] === 404, 'unpublish hides the page');

echo "== CMS: page layouts (Default / Full Width / Canvas) + Custom HTML\n";
// save works even when a proxy strips the X-CSRF-TOKEN header (token sent in the body)
function json_post_body_token(string $url, array $fields, string $csrf): array
{
    $fields['_token'] = $csrf;
    $q = http_build_query($fields);
    $r = http('POST', $url, $q, ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json']);
    $r['json'] = json_decode($r['body'], true);

    return $r;
}
$f = form(get('/admin/pages')['body'], '/admin/pages');
set($f['fields'], 'title', 'E2E Canvas HTML');
set($f['fields'], 'slug', $slug.'-canvas');
set($f['fields'], 'template_id', 'html');
$r = submit($f['action'], $f['fields']);
ok($r['code'] === 302 && preg_match('#/admin/pages/(\d+)/builder#', (string) $r['location'], $hm) === 1, 'custom HTML page created');
$hid = (int) ($hm[1] ?? 0);
$hb = get("/admin/pages/{$hid}/builder")['body'];
ok(str_contains($hb, '"template":"canvas"') && str_contains($hb, 'templates:'), 'custom HTML page starts as Canvas');
$htmlLayout = ['version' => 1, 'sections' => [[
    'id' => 'canvassec', 'settings' => ['width' => 'bleed', 'pt' => 0, 'pb' => 0, 'gap' => 'none', 'height' => 'screen', 'content_pos' => 'center'],
    'columns' => [['id' => 'canvascol', 'settings' => ['w' => 100], 'widgets' => [
        widget('html', ['code' => '<section id="e2e-own"><h1>Own landing E2E</h1><p id="e2e-js">x</p></section>', 'css' => '#e2e-own{background:#123456}', 'js' => 'document.getElementById("e2e-js").textContent="ok";', 'wrap' => 'none']),
    ]]],
]]];
$canvasPage = ['title' => 'E2E Canvas HTML', 'slug' => $slug.'-canvas', 'template' => 'canvas', 'custom_css' => 'body.e2e-x{color:red}', 'custom_js' => 'window.__e2eCustom=1;', 'custom_head' => '<meta name="e2e-head" content="1">', 'show_page_header' => false];
$r = json_post_body_token("/admin/pages/{$hid}/save", ['layout' => json_encode($htmlLayout), 'page' => $canvasPage], $csrf);
ok(($r['json']['ok'] ?? false) === true, 'save works with the CSRF token only in the body');
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['page' => $canvasPage], $csrf);
ok(($r['json']['ok'] ?? false) && ($r['json']['status'] ?? '') === 'published', 'publish works with the CSRF token only in the body');
$ch = get('/'.$slug.'-canvas')['body'];
foreach (['Own landing E2E', '#e2e-own{background:#123456}', 'textContent="ok"', 'body.e2e-x{color:red}', 'window.__e2eCustom=1;', 'name="e2e-head"', 'class="pb-pure"'] as $needle) {
    ok(str_contains($ch, $needle), 'pure canvas renders: '.$needle);
}
ok(! str_contains($ch, 'site-header') && ! str_contains($ch, 'site-footer') && ! str_contains($ch, 'assets/css/style.css') && ! str_contains($ch, 'main.js'), 'pure canvas: no theme header/footer/CSS/JS');
// a whole HTML document is sent as it is (plus the page's custom code)
$docLayout = $htmlLayout;
$docLayout['sections'][0]['columns'][0]['widgets'][0]['data']['code'] = "<!DOCTYPE html>\n<html><head><title>My own doc E2E</title></head><body><h1>Doc body E2E</h1></body></html>";
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['layout' => json_encode($docLayout), 'page' => $canvasPage], $csrf);
$dh = get('/'.$slug.'-canvas')['body'];
ok(str_starts_with(ltrim($dh), '<!DOCTYPE html>') && substr_count(strtolower($dh), '<html') === 1 && str_contains($dh, '<title>My own doc E2E</title>') && preg_match('#<meta name="e2e-head" content="1">.*</head>#s', $dh) === 1 && str_contains($dh, 'window.__e2eCustom=1;</script></body>'), 'full HTML document output as-is with custom head/JS');
// a canvas page that also has a normal widget uses the theme
$mixed = $htmlLayout;
$mixed['sections'][0]['columns'][0]['widgets'][] = widget('heading', ['text' => 'Mixed heading E2E']);
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['layout' => json_encode($mixed), 'page' => $canvasPage], $csrf);
$mh = get('/'.$slug.'-canvas')['body'];
ok(str_contains($mh, 'tpl-canvas') && str_contains($mh, 'Mixed heading E2E') && str_contains($mh, 'assets/css/pages.css') && ! str_contains($mh, 'site-header'), 'canvas with builder widgets: theme CSS, still no header/footer');
// an HTML widget whose code was cut off can't swallow the rest of the page
$broken = $htmlLayout;
$broken['sections'][0]['columns'][0]['widgets'] = [widget('html', ['code' => '<div class="cut"><style>.cut{color:red']), widget('heading', ['text' => 'After broken E2E'])];
$canvasPage['template'] = 'default';
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['layout' => json_encode($broken), 'page' => $canvasPage], $csrf);
$bh = get('/'.$slug.'-canvas')['body'];
ok(str_contains($bh, '.cut{color:red</style></div>') && preg_match('#</style></div>\s*</div>.*After broken E2E#s', $bh) === 1, 'unclosed <style>/<div> in an HTML widget are closed');
ok(str_contains($bh, 'theme-dark') && str_contains($bh, 'sc-dark') && str_contains($bh, 'top-dark'), 'inner pages are dark (tech look) by default');
$canvasPage['color_scheme'] = 'dark';
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['layout' => json_encode($broken), 'page' => $canvasPage], $csrf);
$bh = get('/'.$slug.'-canvas')['body'];
ok(str_contains($bh, 'theme-dark') && str_contains($bh, 'sc-dark') && ! str_contains($bh, 'sc-light'), 'page colour scheme can be dark');
$canvasPage['color_scheme'] = 'light';
$canvasPage['template'] = 'full_width';
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['layout' => json_encode($htmlLayout), 'page' => $canvasPage], $csrf);
$fw = get('/'.$slug.'-canvas')['body'];
ok(str_contains($fw, 'tpl-full_width') && str_contains($fw, 'site-header') && ! str_contains($fw, 'class="pb-page-header'), 'full width keeps header/footer, no title banner');
$fwBoxed = $htmlLayout; $fwBoxed['sections'][0]['settings']['width'] = 'boxed'; $fwBoxed['sections'][0]['columns'][0]['widgets'][] = widget('heading', ['text' => 'FW heading E2E']);
$r = json_post_body_token("/admin/pages/{$hid}/publish", ['layout' => json_encode($fwBoxed), 'page' => $canvasPage], $csrf);
ok(str_contains(get('/'.$slug.'-canvas')['body'], 'w-full'), 'full width page: "boxed" sections become full width');
$canvasPage['template'] = 'evil';
$r = json_post_body_token("/admin/pages/{$hid}/save", ['layout' => json_encode($htmlLayout), 'page' => $canvasPage], $csrf);
ok($r['code'] === 422, 'unknown page layout refused');
$r = json_post_body_token('/admin/client-log', ['message' => 'E2E client log', 'context' => 'test'], $csrf);
ok($r['code'] === 200 || $r['code'] === 204, 'builder client-log endpoint accepts reports');

echo "== Media library\n";
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$r = submit('/admin/media', [['_token', $csrf]], ['files[]' => ['Team Photo.png', $png, 'image/png']]);
$j = json_decode($r['body'], true);
$item = $j['items'][0] ?? null;
ok($item && preg_match('#^uploads/\d{4}/\d{2}/team-photo-[a-z0-9]{6}\.png$#', $item['path']) === 1, 'upload to library');
ok(($item['alt'] ?? '') === 'Team Photo', 'alt text suggested from file name');
$r = submit('/admin/media', [['_token', $csrf]], ['files[]' => ['shell.php', '<?php system($_GET[1]);', 'application/x-php']]);
ok(! empty(json_decode($r['body'], true)['errors']), 'php upload refused');
$l = json_decode(get('/admin/media/list?q=team')['body'], true);
ok(($l['total'] ?? 0) >= 1, 'library search');
$r = submit('/admin/media/'.($item['id'] ?? 0), [['_token', $csrf], ['_method', 'PUT'], ['alt', 'Our team at work'], ['caption', 'Cap']]);
ok(($r['code'] === 200) && (json_decode($r['body'], true)['item']['alt'] ?? '') === 'Our team at work', 'alt text edited');
$r = submit('/admin/media/sync', [['_token', $csrf]]);
ok($r['code'] === 302, 'sync existing uploads');
$file = $root.'/public/'.($item['path'] ?? 'x');
ok(is_file($file), 'file exists before delete');
$r = submit('/admin/media/'.($item['id'] ?? 0), [['_token', $csrf], ['_method', 'DELETE']]);
clearstatcache();
ok($r['code'] === 200 && ! is_file($file), 'delete removes record and file');

echo "== Clean up\n";
$r = submit("/admin/pages/{$id}", [['_token', $csrf], ['_method', 'DELETE']]);
ok($r['code'] === 302, 'page deleted');
ok(get('/does-not-exist-e2e')['code'] === 404, 'unknown page 404');
ok(get('/')['code'] === 200 && str_contains(get('/')['body'], 'swiper-wrapper'), 'home page untouched');

echo $failures ? "\nBUILDER TEST FAILED ({$failures})\n" : "\nBUILDER TEST PASSED\n";
exit($failures ? 1 : 0);
