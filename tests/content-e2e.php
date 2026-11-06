<?php
/**
 * End-to-end test of phase 2: content pages, 22 service pages, menus, forms + inbox,
 * search, the custom 404 page, legal drafts and the content seeder's "never overwrite" rules.
 *
 *   php tests/content-e2e.php http://127.0.0.1:8000 admin@example.com 'Secret@12345'
 */

[$script, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8000', 'admin@example.com', 'Secret@12345'];
require __DIR__.'/lib.php';

function login(string $email, string $password): void
{
    $p = get('/admin/login');
    if ($p['code'] === 302) {
        return;
    }
    $f = form($p['body'], '/admin/login');
    set($f['fields'], 'email', $email);
    set($f['fields'], 'password', $password);
    ok(submit($f['action'], $f['fields'])['code'] === 302, 'logged in');
}

$services = [];
foreach (glob(__DIR__.'/../database/content/services/*.php') as $f) {
    $services += require $f;
}

echo "== Content pages\n";
ok(count($services) === 22, '22 services defined ('.count($services).')');
$titles = [];
foreach ($services as $slug => $s) {
    $p = get('/services/'.$slug);
    $html = html_entity_decode($p['body'], ENT_QUOTES);
    $text = preg_replace('/\s+/', ' ', strip_tags($html));
    $ok = $p['code'] === 200 && str_contains($text, $s['hero']['title']) && str_contains($html, $s['faq'][0][0]) && str_contains($html, '"@type":"FAQPage"') && str_contains($html, '<title>'.$s['seo_title'].'</title>') && str_contains($html, 'start-a-project?service='.$slug);
    ok($ok, "service page /services/{$slug}");
    $titles[] = $s['hero']['title'];
}
ok(count(array_unique($titles)) === 22, 'every service page has its own headline');
foreach (['about' => 'Who we are', 'services' => 'Services for building and growing online', 'our-process' => 'Step by step', 'faq' => 'How do I start a project?', 'tools-and-technologies' => 'The right tool for the job', 'contact' => 'Get in touch', 'start-a-project' => 'What happens next'] as $slug => $needle) {
    $p = get('/'.$slug);
    ok($p['code'] === 200 && str_contains(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($p['body'], ENT_QUOTES))), $needle), "page /{$slug}");
}
$about = get('/about')['body'];
ok(! preg_match('/lorem ipsum/i', $about.get('/services')['body'].get('/faq')['body']), 'no Lorem ipsum');

$svc = get('/services')['body'];
ok(substr_count($svc, '/services/') >= 22, 'services overview links every service');
ok(str_contains(get('/')['body'], 'href="'.$base.'/services/website-development"'), 'home service card links to its page');
ok(substr_count(get('/')['body'], 'swiper-slide sbox') === 4, 'home still shows its 4 service cards');

echo "== Legal drafts and menus\n";
foreach (['privacy-policy', 'terms-and-conditions', 'refund-policy', 'digital-product-delivery-policy', 'shipping-policy'] as $slug) {
    ok(get('/'.$slug)['code'] === 404, "legal draft /{$slug} is not public yet");
}
ok(! str_contains($about, '/privacy-policy"'), 'menus hide links to unpublished pages');
ok(str_contains($about, 'theme-dark') && str_contains($about, 'tv-s-'), 'inner pages are dark with an animated tech scene');

echo "== Search and 404\n";
$s = get('/search?q=n8n');
ok($s['code'] === 200 && str_contains($s['body'], '/services/n8n-automation') && str_contains($s['body'], '<mark>'), 'search finds the n8n page');
ok(str_contains(get('/search?q=zzqqxx')['body'], 'No results'), 'search: no results message');
$nf = get('/this-page-does-not-exist');
ok($nf['code'] === 404 && str_contains($nf['body'], 'Page not found') && str_contains($nf['body'], 'pb-search'), 'custom 404 page with search');

echo "== Forms\n";
$page = get('/start-a-project?service=ai-agents');
preg_match('/name="_token" value="([^"]+)"/', $page['body'], $tm);
preg_match('/name="_t" value="([^"]+)"/', $page['body'], $tt);
ok(str_contains($page['body'], 'selected>AI Agents &amp; AI-Powered Business Solutions'), 'service pre-selected from ?service=');
$post = fn (array $f) => http('POST', '/forms/quote', http_build_query($f), ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json', 'Referer: '.$base.'/start-a-project']);
$base_fields = ['_token' => $tm[1] ?? '', '_t' => html_entity_decode($tt[1] ?? ''), 'name' => 'E2E Client', 'email' => 'e2e-client@example.com', 'service' => 'AI Agents & AI-Powered Business Solutions', 'budget' => 'Not sure yet', 'timeline' => 'Flexible', 'message' => 'We would like an assistant for our support team. E2E.', 'consent' => '1'];
sleep(3); // forms filled faster than 3 seconds are treated as bots
$r = $post(array_merge($base_fields, ['email' => 'not-an-email', 'consent' => '']));
$j = json_decode($r['body'], true);
ok($r['code'] === 422 && isset($j['errors']['email'], $j['errors']['consent']), 'invalid form → 422 with field errors');
$r = $post($base_fields);
ok($r['code'] === 200 && (json_decode($r['body'], true)['ok'] ?? false), 'valid quote request accepted');
$r = $post(array_merge($base_fields, ['website' => 'http://spam.example', 'name' => 'Spam Bot E2E']));
ok($r['code'] === 200, 'honeypot submission gets a quiet success');

login($email, $password);
$inbox = get('/admin/messages')['body'];
ok(str_contains($inbox, 'E2E Client') && ! str_contains($inbox, 'Spam Bot E2E'), 'message in admin inbox, spam not stored');
preg_match('#/admin/messages/(\d+)"[^>]*>E2E Client#', $inbox, $mm);
$show = get('/admin/messages/'.($mm[1] ?? 0))['body'];
ok(str_contains($show, 'We would like an assistant') && str_contains($show, 'Not sure yet'), 'message details visible');
ok(str_contains(get('/admin/settings/mail')['body'], 'SMTP'), 'email (SMTP) settings page');
preg_match('/name="_token" value="([^"]+)"/', $show, $ct);
$r = submit('/admin/messages/'.($mm[1] ?? 0), [['_token', $ct[1] ?? ''], ['_method', 'DELETE']]);
ok($r['code'] === 302, 'message deleted');

echo "== Content seeder never overwrites\n";
$pagesList = get('/admin/pages?q=our-process')['body'];
preg_match('#/admin/pages/(\d+)/builder#', $pagesList, $pm);
$pid = (int) ($pm[1] ?? 0);
ok($pid > 0, 'seeded page is editable in the builder');
$csrf = '';
if (preg_match('#csrf: "([^"]+)"#', get("/admin/pages/{$pid}/builder")['body'], $cm)) {
    $csrf = stripcslashes($cm[1]);
}
$r = submit("/admin/pages/{$pid}", [['_token', $csrf], ['_method', 'DELETE']]);
ok($r['code'] === 302 && get('/our-process')['code'] === 404, 'owner can delete a seeded page');
exec('php '.escapeshellarg(__DIR__.'/../artisan').' db:seed --class="Database\\Seeders\\ContentSeeder" --force 2>&1', $out, $code);
ok($code === 0 && get('/our-process')['code'] === 404, 're-running the seeder does not bring a deleted page back');
ok(substr_count(get('/services')['body'], 'href="'.$base.'/services/n8n-automation"') >= 1, 'services not duplicated by re-run');

echo $failures ? "\nCONTENT TEST FAILED ({$failures})\n" : "\nCONTENT TEST PASSED\n";
exit($failures ? 1 : 0);
