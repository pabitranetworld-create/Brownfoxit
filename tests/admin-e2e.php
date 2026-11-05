<?php
/**
 * End-to-end test of the admin panel, acting like a browser:
 * logs in, opens every page, re-submits every settings form unchanged
 * (nothing may be lost), edits content, uploads an image, creates/deletes records.
 *
 *   php tests/admin-e2e.php http://127.0.0.1:8000 admin@example.com 'Secret@12345'
 */

[$script, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8000', 'admin@example.com', 'Secret@12345'];
$base = rtrim($base, '/');
$root = dirname(__DIR__);
$jar = tempnam(sys_get_temp_dir(), 'jar');
$failures = 0;

function ok(bool $cond, string $msg): void
{
    global $failures;
    echo ($cond ? "  ✓ " : "  ✗ ").$msg."\n";
    if (! $cond) {
        $failures++;
        echo "::error::{$msg}\n";
    }
}

function http(string $method, string $url, ?string $body = null, array $headers = []): array
{
    global $base, $jar;
    $ch = curl_init(str_starts_with($url, 'http') ? $url : $base.$url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $head = substr($raw, 0, $hsize);
    preg_match('/^Location:\s*(.+)$/mi', $head, $m);

    return ['code' => $code, 'body' => substr($raw, $hsize), 'location' => isset($m[1]) ? trim($m[1]) : null];
}

function get(string $url): array
{
    return http('GET', $url);
}

/** Follow redirects (GET) and return the final page. */
function follow(array $res): array
{
    $n = 0;
    while (in_array($res['code'], [301, 302, 303], true) && $res['location'] && $n++ < 5) {
        $res = get($res['location']);
    }

    return $res;
}

/**
 * Collect a form's fields exactly like a browser would (skipping <template> content).
 * @return array{action:string, fields:array<int,array{0:string,1:string}>}
 */
function form(string $html, string $actionContains): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xp = new DOMXPath($dom);

    foreach ($dom->getElementsByTagName('form') as $form) {
        $action = $form->getAttribute('action');
        if (! str_contains($action, $actionContains)) {
            continue;
        }
        $fields = [];
        foreach ($xp->query('.//input|.//textarea|.//select', $form) as $el) {
            if ($xp->query('ancestor::template', $el)->length) {
                continue;
            }
            $name = $el->getAttribute('name');
            if ($name === '') {
                continue;
            }
            $tag = $el->nodeName;
            $type = strtolower($el->getAttribute('type'));
            if ($tag === 'input' && in_array($type, ['file', 'submit', 'button'], true)) {
                continue;
            }
            if ($tag === 'input' && $type === 'checkbox' && ! $el->hasAttribute('checked')) {
                continue;
            }
            if ($tag === 'textarea') {
                $value = $el->textContent;
                $value = preg_replace('/^\r?\n/', '', $value); // browsers drop the first newline
            } elseif ($tag === 'select') {
                $value = '';
                $opts = $xp->query('.//option', $el);
                foreach ($opts as $i => $opt) {
                    if ($i === 0 || $opt->hasAttribute('selected')) {
                        $value = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $opt->textContent;
                        if ($opt->hasAttribute('selected')) {
                            break;
                        }
                    }
                }
            } else {
                $value = $el->getAttribute('value');
            }
            $fields[] = [$name, $value];
        }

        return ['action' => $action, 'fields' => $fields];
    }

    throw new RuntimeException("form with action containing '{$actionContains}' not found");
}

function set(array &$fields, string $name, string $value): void
{
    foreach ($fields as $i => [$n]) {
        if ($n === $name) {
            $fields[$i][1] = $value;

            return;
        }
    }
    $fields[] = [$name, $value];
}

/** Send a multipart form (supports repeated names like a[] and files). */
function submit(string $action, array $fields, array $files = []): array
{
    $boundary = '----bfi'.bin2hex(random_bytes(8));
    $body = '';
    foreach ($fields as [$name, $value]) {
        $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"\r\n\r\n{$value}\r\n";
    }
    foreach ($files as $name => [$filename, $content, $mime]) {
        $body .= "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\nContent-Type: {$mime}\r\n\r\n{$content}\r\n";
    }
    $body .= "--{$boundary}--\r\n";

    return http('POST', $action, $body, ["Content-Type: multipart/form-data; boundary={$boundary}", 'Accept: text/html']);
}

function settingsJson(): array
{
    global $root;
    $out = shell_exec('cd '.escapeshellarg($root).' && php artisan tinker --execute='.escapeshellarg('echo json_encode(App\Models\Setting::query()->orderBy("key")->get()->mapWithKeys(fn($s)=>[$s->key=>$s->value]));').' 2>&1');

    return json_decode(trim((string) $out), true) ?? ['_raw' => $out];
}

/** Drop nulls, empty strings, empty arrays and "_row" markers so "missing" == "empty". */
function clean(mixed $v): mixed
{
    if (! is_array($v)) {
        return $v;
    }
    $out = [];
    foreach ($v as $k => $x) {
        if ($k === '_row') {
            continue;
        }
        $x = clean($x);
        if ($x === null || $x === '' || $x === []) {
            continue;
        }
        $out[$k] = $x;
    }

    return array_is_list($v) ? array_values($out) : $out;
}

/* ===================================================================== */

echo "== Admin: login\n";
$r = get('/admin');
ok($r['code'] === 302 && str_contains((string) $r['location'], '/admin/login'), 'guests are sent to /admin/login');

$page = get('/admin/login');
ok($page['code'] === 200 && str_contains($page['body'], 'Admin লগইন'), 'login page loads');

$f = form($page['body'], '/admin/login');
set($f['fields'], 'email', $email);
set($f['fields'], 'password', 'wrong-password');
$r = follow(submit($f['action'], $f['fields']));
ok(str_contains($r['body'], 'ইমেইল বা পাসওয়ার্ড ভুল'), 'wrong password is rejected');

$f = form($r['body'], '/admin/login');
set($f['fields'], 'email', $email);
set($f['fields'], 'password', $password);
$r = submit($f['action'], $f['fields']);
ok($r['code'] === 302 && str_ends_with(rtrim((string) $r['location'], '/'), '/admin'), 'correct password logs in');

$dash = get('/admin');
ok($dash['code'] === 200 && str_contains($dash['body'], 'স্বাগতম'), 'dashboard loads');

echo "== Admin: every settings form round-trips without losing data\n";
$before = settingsJson();
preg_match_all('#/admin/settings/([a-z_]+)"#', $dash['body'], $m);
$sections = array_values(array_unique($m[1]));
ok(count($sections) >= 13, 'all settings sections are linked ('.count($sections).')');
foreach ($sections as $s) {
    $p = get("/admin/settings/{$s}");
    if (! ok_page($p, "settings/{$s} opens")) {
        continue;
    }
    $f = form($p['body'], "/admin/settings/{$s}");
    $r = follow(submit($f['action'], $f['fields']));
    ok(str_contains($r['body'], 'সেভ হয়েছে'), "settings/{$s} saves unchanged form");
}
$after = settingsJson();
foreach ($before as $key => $value) {
    $same = clean($value) == clean($after[$key] ?? null);
    ok($same, "setting '{$key}' unchanged after save");
    if (! $same) {
        echo '    before: '.json_encode(clean($value), JSON_UNESCAPED_UNICODE)."\n";
        echo '    after:  '.json_encode(clean($after[$key] ?? null), JSON_UNESCAPED_UNICODE)."\n";
    }
}

function ok_page(array $p, string $msg): bool
{
    $good = $p['code'] === 200 && ! str_contains($p['body'], 'Whoops') && ! str_contains($p['body'], 'Server Error');
    ok($good, $msg.($good ? '' : " (HTTP {$p['code']})"));
    if (! $good) {
        echo substr(strip_tags($p['body']), 0, 1500)."\n";
    }

    return $good;
}

echo "== Admin: edit hero title + upload a photo\n";
$p = get('/admin/settings/hero');
$f = form($p['body'], '/admin/settings/hero');
set($f['fields'], 'data[title]', 'Brown Fox IT E2E');
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$r = follow(submit($f['action'], $f['fields'], ['data_files[person_image]' => ['my photo.png', $png, 'image/png']]));
ok(str_contains($r['body'], 'সেভ হয়েছে'), 'hero saved with upload');
$hero = settingsJson()['hero'] ?? [];
ok(($hero['title'] ?? '') === 'Brown Fox IT E2E', 'hero title stored');
ok(preg_match('#^uploads/\d{4}/\d{2}/my-photo-[a-z0-9]{6}\.png$#', $hero['person_image'] ?? '') === 1, 'photo stored as '.($hero['person_image'] ?? '-'));
ok(is_file($root.'/public/'.($hero['person_image'] ?? 'x')), 'uploaded file exists in public/uploads');
ok(($hero['rating_value'] ?? '') === '4.9 (13,090 )', 'other hero fields kept');
$home = get('/');
ok(str_contains($home['body'], 'Brown Fox IT E2E') && str_contains($home['body'], $hero['person_image'] ?? 'x'), 'home page shows new title and photo');

echo "== Admin: rejects a non-image upload\n";
$p = get('/admin/settings/about');
$f = form($p['body'], '/admin/settings/about');
$r = follow(submit($f['action'], $f['fields'], ['data_files[image]' => ['evil.php', '<?php echo 1;', 'application/x-php']]));
ok(str_contains($r['body'], 'শুধু JPG'), 'php file refused');
ok(! glob($root.'/public/uploads/*/*/evil*'), 'nothing saved to uploads');

echo "== Admin: repeater add row (counters)\n";
$p = get('/admin/settings/counters');
$f = form($p['body'], '/admin/settings/counters');
$f['fields'][] = ['data[_root][nx1][_row]', '1'];
$f['fields'][] = ['data[_root][nx1][value]', '99'];
$f['fields'][] = ['data[_root][nx1][suffix]', '+'];
$f['fields'][] = ['data[_root][nx1][label]', 'E2E Counter'];
$f['fields'][] = ['data[_root][nx1][color]', 'green'];
follow(submit($f['action'], $f['fields']));
$counters = settingsJson()['counters'] ?? [];
ok(count($counters) === 5 && ($counters[4]['label'] ?? '') === 'E2E Counter', 'new counter row saved at the end');
ok(str_contains(get('/')['body'], 'E2E Counter'), 'home page shows new counter');

echo "== Admin: content tables\n";
foreach (['services', 'projects', 'testimonials', 'clients', 'posts'] as $res) {
    ok_page(get("/admin/content/{$res}"), "content/{$res} list");
    ok_page(get("/admin/content/{$res}/create"), "content/{$res} create form");
    $list = get("/admin/content/{$res}")['body'];
    if (preg_match("#/admin/content/{$res}/(\d+)/edit#", $list, $mm)) {
        $p = get("/admin/content/{$res}/{$mm[1]}/edit");
        if (ok_page($p, "content/{$res} edit form")) {
            $f = form($p['body'], "/admin/content/{$res}/{$mm[1]}");
            $r = follow(submit($f['action'], $f['fields']));
            ok(str_contains($r['body'], 'সেভ হয়েছে'), "content/{$res} saves unchanged");
        }
    }
}

// create a service
$p = get('/admin/content/services/create');
$f = form($p['body'], '/admin/content/services');
set($f['fields'], 'data[title]', 'E2E Service');
set($f['fields'], 'data[icon]', 'fa-solid fa-bolt');
set($f['fields'], 'data[short_description]', 'Created by the test');
$r = submit($f['action'], $f['fields']);
ok($r['code'] === 302 && preg_match('#/admin/content/services/(\d+)/edit#', (string) $r['location'], $sm) === 1, 'service created');
ok(str_contains(get('/')['body'], 'E2E Service'), 'home page shows new service');

// required field check
$p = get('/admin/content/services/create');
$f = form($p['body'], '/admin/content/services');
$r = follow(submit($f['action'], $f['fields']));
ok(str_contains($r['body'], 'খালি রাখা যাবে না'), 'empty title refused');

// create a post with a new category
$p = get('/admin/content/posts/create');
$f = form($p['body'], '/admin/content/posts');
set($f['fields'], 'data[title]', 'E2E Blog Post');
set($f['fields'], 'data[post_category_id_new]', 'E2E Category');
set($f['fields'], 'data[body]', '<p>Hello</p>');
$r = submit($f['action'], $f['fields']);
ok($r['code'] === 302, 'post created');
$home = get('/')['body'];
ok(str_contains($home, 'E2E Blog Post') && str_contains($home, 'E2E Category'), 'home page shows new post with new category');

// delete the service
if (! empty($sm[1])) {
    $p = get("/admin/content/services/{$sm[1]}/edit");
    $f = form($p['body'], "/admin/content/services/{$sm[1]}");
    $del = form($p['body'], "/admin/content/services/{$sm[1]}"); // first form = update; find delete form:
    preg_match('#<form id="delete-form"[^>]*action="([^"]+)"#', $p['body'], $dm);
    $token = '';
    foreach ($f['fields'] as [$n, $v]) {
        if ($n === '_token') {
            $token = $v;
        }
    }
    $r = submit(html_entity_decode($dm[1] ?? ''), [['_token', $token], ['_method', 'DELETE']]);
    ok($r['code'] === 302 && ! str_contains(get('/')['body'], 'E2E Service'), 'service deleted');
}

echo "== Admin: profile + logout\n";
ok_page(get('/admin/profile'), 'profile page');
$p = get('/admin');
preg_match('#name="_token" value="([^"]+)"#', $p['body'], $tm);
$r = submit('/admin/logout', [['_token', $tm[1] ?? '']]);
ok($r['code'] === 302, 'logout');
$r = get('/admin');
ok($r['code'] === 302, 'admin closed again after logout');

echo $failures ? "\nADMIN TEST FAILED ({$failures})\n" : "\nADMIN TEST PASSED\n";
exit($failures ? 1 : 0);
