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
require __DIR__.'/lib.php';

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
$countersBefore = count(settingsJson()['counters'] ?? []);
$p = get('/admin/settings/counters');
$f = form($p['body'], '/admin/settings/counters');
$f['fields'][] = ['data[_root][nx1][_row]', '1'];
$f['fields'][] = ['data[_root][nx1][value]', '99'];
$f['fields'][] = ['data[_root][nx1][suffix]', '+'];
$f['fields'][] = ['data[_root][nx1][label]', 'E2E Counter'];
$f['fields'][] = ['data[_root][nx1][color]', 'green'];
follow(submit($f['action'], $f['fields']));
$counters = settingsJson()['counters'] ?? [];
ok(count($counters) === $countersBefore + 1 && (end($counters)['label'] ?? '') === 'E2E Counter', 'new counter row saved at the end');
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
ok(str_contains(get('/services')['body'], 'E2E Service'), 'Services page shows new service (catalogue)');

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
