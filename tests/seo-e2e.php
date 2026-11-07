<?php
/**
 * SEO system end-to-end test: head tags on every public URL, sitemap, robots.txt, transactional noindex,
 * 404s, redirect manager (loops, chains, duplicates), automatic redirects on URL change, the SEO editor,
 * structured data, draft-domain hiding, preferred-host redirect, audit, and the Gmail mail setup screens.
 *
 *   php tests/seo-e2e.php http://127.0.0.1:8000 admin@example.com 'Secret@12345'   (run from the app folder)
 */

[$script, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8000', 'admin@example.com', 'Secret@12345'];
require __DIR__.'/lib.php';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Seo\Subjects;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

$tag = Str::lower(Str::random(5));
$seoBackup = Setting::get('seo', []);
$mailBackup = Setting::get('mail', []);
$setSeo = function (array $v) use ($seoBackup) {
    Setting::put('seo', array_merge((array) $seoBackup, ['production_host' => '', 'visibility' => 'auto'], $v));
    Cache::flush();
};
$setSeo([]);

function head(string $html): array
{
    preg_match_all('#<title>(.*?)</title>#s', $html, $t);
    preg_match_all('#<meta name="description" content="([^"]*)"#', $html, $d);
    preg_match_all('#<link rel="canonical" href="([^"]*)"#', $html, $c);
    preg_match_all('#<meta name="robots" content="([^"]*)"#', $html, $r);
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $j);

    return ['title' => $t[1], 'desc' => $d[1], 'canonical' => $c[1], 'robots' => $r[1], 'jsonld' => $j[1]];
}

echo "== Every public URL has one title, one canonical, one robots tag and valid JSON-LD\n";
$bad = [];
$checked = 0;
foreach (array_slice(Subjects::all(true), 0, 80) as [$type, $key, $s]) {
    $path = Subjects::path($s);
    $r = get($path);
    if ($r['code'] !== 200) {
        $bad[] = "$path → {$r['code']}";

        continue;
    }
    $h = head($r['body']);
    $checked++;
    if (count($h['title']) !== 1 || count($h['canonical']) !== 1 || count($h['robots']) !== 1 || count($h['desc']) > 1 || count($h['jsonld']) !== 1) {
        $bad[] = "$path: title ".count($h['title']).', canonical '.count($h['canonical']).', robots '.count($h['robots']).', desc '.count($h['desc']).', json-ld '.count($h['jsonld']);
    } elseif (! is_array(json_decode(html_entity_decode($h['jsonld'][0]), true)) && ! is_array(json_decode($h['jsonld'][0], true))) {
        $bad[] = "$path: invalid JSON-LD";
    } elseif (str_contains($h['jsonld'][0], 'aggregateRating') || str_contains($h['jsonld'][0], 'FAQPage')) {
        $bad[] = "$path: ratings or FAQPage in JSON-LD";
    }
}
ok($checked > 5 && ! $bad, "checked {$checked} pages".($bad ? ': '.implode(' | ', array_slice($bad, 0, 6)) : ''));

echo "== Canonicals and transactional pages\n";
$h = head(get('/shop?utm_source=news&page=1&sort=price')['body']);
ok(str_ends_with($h['canonical'][0] ?? '', '/shop'), 'tracking / sort / page=1 parameters are dropped from the canonical');
foreach (['/cart', '/checkout', '/account/login', '/search?q=web'] as $p) {
    $r = get($p);
    ok(in_array($r['code'], [200, 302], true) && ($r['code'] === 302 || str_contains(head($r['body'])['robots'][0] ?? '', 'noindex')), "$p is noindex");
}
foreach (['/this-does-not-exist-'.$tag, '/blog/nope-'.$tag, '/shop/nope-'.$tag, '/portfolio/nope-'.$tag] as $p) {
    ok(get($p)['code'] === 404, "$p → 404");
}

echo "== robots.txt and sitemap\n";
$robots = get('/robots.txt');
ok($robots['code'] === 200 && str_contains($robots['body'], 'Sitemap: ') && str_contains($robots['body'], 'Disallow: /admin') && ! preg_match('#Disallow: /\s*$#m', $robots['body']), 'robots.txt allows the site, blocks admin, lists the sitemap');
$idx = get('/sitemap.xml');
$xml = @simplexml_load_string($idx['body']);
ok($idx['code'] === 200 && $xml && $xml->getName() === 'sitemapindex', 'sitemap index is valid XML');
$all = '';
foreach ($xml->sitemap ?? [] as $sm) {
    $path = parse_url((string) $sm->loc, PHP_URL_PATH);
    $part = get($path);
    ok($part['code'] === 200 && @simplexml_load_string($part['body']) !== false, "$path valid");
    $all .= $part['body'];
}
$post = Post::published()->first();
if ($post) {
    ok(str_contains($all, '/blog/'.$post->slug.'</loc>'), 'a published post is in the sitemap');
}
$draftProduct = Product::where('status', 'draft')->first();
if ($draftProduct) {
    ok(! str_contains($all, '/shop/'.$draftProduct->slug.'</loc>'), 'draft products are not in the sitemap');
}
ok(! str_contains($all, '/cart</loc>') && ! str_contains($all, '/page-not-found</loc>') && ! str_contains($all, '/admin'), 'utility pages are not in the sitemap');
ok(! preg_match('#<loc>[^<]*\?#', $all), 'no parameter URLs in the sitemap');

echo "== Admin: SEO screens\n";
$p = get('/admin/login');
if ($p['code'] !== 302) {
    $lf = form($p['body'], '/admin/login');
    set($lf['fields'], 'email', $email);
    set($lf['fields'], 'password', $password);
    submit($lf['action'], $lf['fields']);
}
$about = App\Models\Page::where('slug', 'about')->first() ?: App\Models\Page::published()->first();
foreach (['/admin/seo', '/admin/seo/pages', '/admin/seo/pages?show=problems', '/admin/redirects', '/admin/mail', '/admin/settings/seo', '/admin/settings/seo_integrations', '/admin/seo/route/home', '/admin/seo/page/'.$about->id, '/admin/content/post_categories'] as $u) {
    $r = get($u);
    ok($r['code'] === 200, "admin $u");
}
$audit = http('POST', '/admin/seo/audit', json_encode(['offset' => 0]), ['Content-Type: application/json', 'Accept: application/json', 'X-CSRF-TOKEN: '.csrf_from(get('/admin/seo')['body'])]);
$aj = json_decode($audit['body'], true);
ok($audit['code'] === 200 && ($aj['total'] ?? 0) > 0 && ! empty($aj['done']), 'audit runs (first batch: '.implode(', ', array_slice($aj['done'] ?? [], 0, 2)).')');

echo "== SEO editor\n";
$post = Post::create(['title' => 'SEO E2E Post '.$tag, 'slug' => 'seo-e2e-'.$tag, 'excerpt' => 'Excerpt for the SEO test post.', 'body' => '<p>'.str_repeat('Website automation workflows for small business owners. ', 40).'</p>', 'status' => 'published', 'published_at' => now()->subMinute()]);
$ed = get('/admin/seo/post/'.$post->id);
ok($ed['code'] === 200 && str_contains($ed['body'], 'seo-serp'), 'editor opens with a search preview');
$f = form($ed['body'], '/admin/seo/post/'.$post->id);
set($f['fields'], 'title', 'Custom SEO Title '.$tag.' | Brown Fox IT');
set($f['fields'], 'description', 'A custom description written for the SEO end-to-end test of '.$tag.', long enough to be useful in results.');
set($f['fields'], 'focus_keyword', 'website automation');
set($f['fields'], 'schema_override', '{"@type":"Thing","aggregateRating":{"ratingValue":5}}');
$bad = follow(submit($f['action'], $f['fields']));
ok(str_contains($bad['body'], 'Ratings') || str_contains($bad['body'], 'aggregateRating'), 'invented ratings in custom JSON-LD are refused');
set($f['fields'], 'schema_override', '{not json');
$bad = follow(submit($f['action'], $f['fields']));
ok(str_contains($bad['body'], 'Not valid JSON'), 'invalid JSON-LD is refused');
set($f['fields'], 'schema_override', '');
follow(submit($f['action'], $f['fields']));
$h = head(get('/blog/seo-e2e-'.$tag)['body']);
ok(($h['title'][0] ?? '') === 'Custom SEO Title '.$tag.' | Brown Fox IT' && str_contains($h['desc'][0] ?? '', 'custom description'), 'saved title and description are used on the page');
ok($post->fresh()->seo_title === 'Custom SEO Title '.$tag.' | Brown Fox IT', 'same value visible in the post editor (single source)');
$ld = json_decode(html_entity_decode($h['jsonld'][0] ?? ''), true) ?: json_decode($h['jsonld'][0] ?? '', true);
$types = array_column($ld['@graph'] ?? [], '@type');
ok(in_array('BlogPosting', $types, true) && in_array('BreadcrumbList', $types, true) && in_array('Organization', $types, true) && in_array('WebSite', $types, true), 'post JSON-LD: '.implode(', ', $types));

// URL change → automatic 301
$ed = get('/admin/seo/post/'.$post->id);
$f = form($ed['body'], '/admin/seo/post/'.$post->id);
set($f['fields'], 'slug', 'seo-e2e-new-'.$tag);
follow(submit($f['action'], $f['fields']));
$old = get('/blog/seo-e2e-'.$tag);
ok($old['code'] === 301 && str_ends_with((string) $old['location'], '/blog/seo-e2e-new-'.$tag), 'old post URL → 301 to the new URL');
ok(get('/blog/seo-e2e-new-'.$tag)['code'] === 200, 'new URL works');

// noindex → out of the sitemap
$ed = get('/admin/seo/post/'.$post->id);
$f = form($ed['body'], '/admin/seo/post/'.$post->id);
set($f['fields'], 'noindex', '1');
follow(submit($f['action'], $f['fields']));
ok(str_contains(head(get('/blog/seo-e2e-new-'.$tag)['body'])['robots'][0] ?? '', 'noindex'), 'noindex is printed');
ok(! str_contains(get('/sitemap-posts.xml')['body'], 'seo-e2e-new-'.$tag), 'noindex post left the sitemap');

echo "== Redirect manager\n";
$rp = get('/admin/redirects');
$f = form($rp['body'], '/admin/redirects');
foreach (['from_path' => '/old-e2e-'.$tag.'/', 'to_url' => '/about', 'status_code' => '301'] as $k => $v) {
    set($f['fields'], $k, $v);
}
follow(submit($f['action'], $f['fields']));
$r = get('/old-e2e-'.$tag.'?utm_source=x');
ok($r['code'] === 301 && str_contains((string) $r['location'], '/about?utm_source=x'), 'redirect works (trailing slash + query kept)');
$r = get('/OLD-E2E-'.$tag);
ok($r['code'] === 301, 'redirect matching ignores letter case');
foreach ([['/old-e2e-'.$tag, '/contact', 'already exists'], ['/about', '/old-e2e-'.$tag, 'loop'], ['/admin/x-'.$tag, '/about', 'System']] as [$from, $to, $why]) {
    $f = form(get('/admin/redirects')['body'], '/admin/redirects');
    set($f['fields'], 'from_path', $from);
    set($f['fields'], 'to_url', $to);
    $res = follow(submit($f['action'], $f['fields']));
    ok(str_contains($res['body'], $why), "refused: $from → $to ($why)");
}
$f = form(get('/admin/redirects')['body'], '/admin/redirects');
set($f['fields'], 'from_path', '/chain-e2e-'.$tag);
set($f['fields'], 'to_url', '/old-e2e-'.$tag);
follow(submit($f['action'], $f['fields']));
ok(Redirect::where('from_path', '/chain-e2e-'.$tag)->value('to_url') === '/about', 'a chain is collapsed to the final address');
ok(! Redirect::where('to_url', '/blog/seo-e2e-'.$tag)->exists(), 'no redirect points to an old (redirected) address');

echo "== Draft domain stays hidden, real domain gets one preferred address\n";
$setSeo(['production_host' => 'e2e-real-'.$tag.'.example']);
$r = get('/about');
ok(str_contains(head($r['body'])['robots'][0] ?? '', 'noindex'), 'on another domain every page is noindex');
ok(str_contains(strtolower($r['headers'] ?? ''), 'x-robots-tag: noindex'), 'X-Robots-Tag: noindex header sent');
ok(preg_match('#Disallow: /\s*$#m', get('/robots.txt')['body']) === 1, 'robots.txt blocks everything on the draft domain');
ok(get('/sitemap.xml')['code'] === 404, 'no sitemap on the draft domain');
$host = 'e2e-real-'.$tag.'.example';
$r = http('GET', '/about?x=1', null, ['Host: www.'.$host]);
ok($r['code'] === 301 && $r['location'] === 'https://'.$host.'/about?x=1', 'www → preferred https host (301)');
$r = http('GET', '/about', null, ['Host: '.$host]);
ok($r['code'] === 301 && str_starts_with((string) $r['location'], 'https://'.$host), 'http → https on the real domain');
$r = http('GET', '/about', null, ['Host: '.$host, 'X-Forwarded-Proto: https']);
ok($r['code'] === 200 && str_contains(head($r['body'])['canonical'][0] ?? '', 'https://'.$host.'/about') && str_contains(head($r['body'])['robots'][0] ?? '', 'index, follow'), 'real domain: indexable with https canonical');
$setSeo([]);

echo "== Email / Gmail setup screens\n";
$mp = get('/admin/mail');
$f = form($mp['body'], '/admin/mail/gmail-app-password');
set($f['fields'], 'gmail', 'e2e.'.$tag.'@gmail.com');
set($f['fields'], 'app_password', 'MyNormalPassword1');
$res = follow(submit($f['action'], $f['fields']));
ok(str_contains($res['body'], '16'), 'a normal password is refused (needs the 16-letter App Password)');
$f = form(get('/admin/mail')['body'], '/admin/mail/gmail-app-password');
set($f['fields'], 'gmail', 'e2e.'.$tag.'@gmail.com');
set($f['fields'], 'app_password', 'abcd efgh ijkl mnop');
follow(submit($f['action'], $f['fields']));
Cache::flush();
$m = (array) Setting::get('mail');
ok(($m['host'] ?? '') === 'smtp.gmail.com' && (int) ($m['port'] ?? 0) === 465 && ($m['encryption'] ?? '') === 'ssl' && ($m['mailer'] ?? '') === 'smtp', 'Gmail SMTP filled in automatically');
ok(! str_contains(json_encode($m), 'abcdefghijklmnop'), 'App Password stored encrypted');
$f = form(get('/admin/mail')['body'], '/admin/mail/google-client');
set($f['fields'], 'client_id', '123-e2e.apps.googleusercontent.com');
set($f['fields'], 'client_secret', 'GOCSPX-e2e-secret');
follow(submit($f['action'], $f['fields']));
$c = get('/admin/mail/google/connect');
ok($c['code'] === 302 && str_starts_with((string) $c['location'], 'https://accounts.google.com/o/oauth2/v2/auth?') && str_contains($c['location'], 'gmail.send') && str_contains($c['location'], 'state='), 'Connect with Google sends the owner to Google (send-only scope, state)');
$cb = follow(get('/admin/mail/google/callback?state=wrong&code=x'));
ok(str_contains($cb['body'], 'state'), 'a callback with a wrong state is refused');
Setting::put('mail', $mailBackup);
Setting::put('seo', $seoBackup);
Cache::flush();
Post::where('slug', 'like', 'seo-e2e-%')->delete();
Redirect::where('from_path', 'like', '%-e2e-'.$tag.'%')->delete();

if ($failures) {
    echo "\n{$failures} check(s) failed\n";
    exit(1);
}
echo "\nAll SEO checks passed\n";

function csrf_from(string $html): string
{
    return preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m) ? $m[1] : (preg_match('/name="_token" value="([^"]+)"/', $html, $m2) ? $m2[1] : '');
}
