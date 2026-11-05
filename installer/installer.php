<?php
/**
 * Brown Fox IT – Web Installer
 * ------------------------------------------------------------------
 * public/index.php loads this file while storage/installed.json does not exist.
 * It runs BEFORE Laravel boots, so it works without a .env file.
 *
 * Steps: requirements → database → website & admin → install → done
 * When it finishes it writes storage/installed.json and is never shown again.
 */

declare(strict_types=1);

define('BFI_ROOT', dirname(__DIR__));
define('BFI_MIN_PHP', '8.2.0');

/* ============================================================
 |  JSON actions (called by the wizard with fetch)
 * ============================================================ */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $input = json_decode((string) file_get_contents('php://input'), true) ?: [];

    try {
        $result = bfi_action((string) $_GET['action'], $input);
        echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

function bfi_action(string $action, array $in): array
{
    @set_time_limit(300);

    switch ($action) {
        case 'requirements':
            return bfi_requirements();

        case 'test-db':
            $pdo = bfi_pdo($in);
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
            $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();

            return ['version' => $version, 'tables' => $tables];

        case 'write-env':
            bfi_pdo($in['db'] ?? []); // fail early if the details are wrong
            $token = bfi_write_env($in);

            return ['message' => '.env ফাইল তৈরি হয়েছে', 'token_set' => $token !== ''];

        case 'migrate':
            bfi_boot();
            Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

            return ['message' => 'ডাটাবেস টেবিল তৈরি হয়েছে', 'log' => trim(Illuminate\Support\Facades\Artisan::output())];

        case 'seed':
            bfi_admin_env($in['admin'] ?? []);
            bfi_boot();
            Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

            return ['message' => 'সাইটের কন্টেন্ট যোগ হয়েছে'];

        case 'admin':
            $admin = bfi_validate_admin($in['admin'] ?? []);
            bfi_boot();
            $user = App\Models\User::firstOrNew(['email' => $admin['email']]);
            $user->forceFill([
                'name' => $admin['name'],
                'password' => $admin['password'], // hashed by the model cast
                'role' => App\Models\User::ROLE_ADMIN,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return ['message' => "Admin অ্যাকাউন্ট তৈরি হয়েছে ({$admin['email']})"];

        case 'images':
            bfi_boot();
            $sync = new App\Support\ImageSync($in['source'] ?? null);
            $skip = max(0, (int) ($in['skip'] ?? 0));
            $batch = array_slice($sync->missing(), $skip, 6, true);
            $failed = [];
            foreach ($batch as $path => $url) {
                if ($error = $sync->download($path, $url)) {
                    $failed[] = "{$path} ({$error})";
                }
            }
            $left = max(0, count($sync->missing()) - $skip - count($failed));

            return ['done' => count($batch) - count($failed), 'failed' => $failed, 'left' => $left];

        case 'finish':
            bfi_boot();
            Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $file = BFI_ROOT.'/storage/installed.json';
            $ok = @file_put_contents($file, json_encode([
                'installed_at' => date('c'),
                'via' => 'web installer',
                'php' => PHP_VERSION,
            ], JSON_PRETTY_PRINT));
            if ($ok === false) {
                throw new RuntimeException('storage/installed.json লেখা যায়নি। storage ফোল্ডারের permission 775 করুন।');
            }

            return [
                'url' => rtrim((string) bfi_env_value('APP_URL'), '/'),
                'deploy_token' => (string) bfi_env_value('DEPLOY_TOKEN'),
            ];
    }

    throw new InvalidArgumentException('Unknown action');
}

/* ============================================================
 |  Helpers
 * ============================================================ */

function bfi_requirements(): array
{
    // Make sure Laravel's folders exist (zip tools sometimes drop empty folders).
    foreach ([
        'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions',
        'storage/framework/views', 'storage/logs', 'bootstrap/cache', 'public/uploads',
    ] as $dir) {
        if (! is_dir(BFI_ROOT.'/'.$dir)) {
            @mkdir(BFI_ROOT.'/'.$dir, 0775, true);
        }
    }

    $checks = [];
    $checks[] = ['label' => 'PHP '.BFI_MIN_PHP.' বা নতুন (আপনার: '.PHP_VERSION.')', 'ok' => version_compare(PHP_VERSION, BFI_MIN_PHP, '>='),
        'help' => 'cPanel → "Select PHP Version" বা "MultiPHP Manager" থেকে PHP 8.2/8.3 বেছে নিন।'];

    foreach (['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'curl', 'dom', 'filter'] as $ext) {
        $checks[] = ['label' => "PHP extension: {$ext}", 'ok' => extension_loaded($ext),
            'help' => 'cPanel → "Select PHP Version" → Extensions থেকে চালু করুন।'];
    }

    $checks[] = ['label' => 'vendor ফোল্ডার (Laravel লাইব্রেরি)', 'ok' => is_file(BFI_ROOT.'/vendor/autoload.php'),
        'help' => 'এটা কোনো একক ফাইল নয়, Laravel-এর লাইব্রেরির পুরো "vendor" ফোল্ডার। সম্ভবত vendor ছাড়া zip আপলোড হয়েছে। GitHub repo → Releases → "Latest install zip" থেকে brownfoxit-install.zip নামিয়ে এই ফোল্ডারেই আবার extract করুন (overwrite)। তারপর "আবার পরীক্ষা" চাপুন।'];

    $envWritable = is_file(BFI_ROOT.'/.env') ? is_writable(BFI_ROOT.'/.env') : is_writable(BFI_ROOT);
    $checks[] = ['label' => '.env ফাইল লেখার অনুমতি', 'ok' => $envWritable, 'help' => 'প্রজেক্টের মূল ফোল্ডারের permission 755 করুন।'];

    foreach (['storage', 'storage/framework', 'storage/logs', 'bootstrap/cache', 'public/uploads'] as $dir) {
        $checks[] = ['label' => "লেখার অনুমতি: {$dir}", 'ok' => is_writable(BFI_ROOT.'/'.$dir),
            'help' => "File Manager → {$dir} → Permissions → 775"];
    }

    $passed = ! in_array(false, array_column($checks, 'ok'), true);

    return [
        'checks' => $checks,
        'passed' => $passed,
        'suggested_url' => bfi_guess_url(),
        'has_env' => is_file(BFI_ROOT.'/.env'),
    ];
}

function bfi_guess_url(): string
{
    $https = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Folder the site lives in (empty when it is the domain root).
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $base = preg_replace('#/public$#', '', rtrim($script, '/'));
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($base !== '' && ! str_starts_with($uri, $base)) {
        $base = ''; // reached through the root .htaccess rewrite
    }

    return ($https ? 'https' : 'http').'://'.$host.$base;
}

function bfi_pdo(array $db): PDO
{
    foreach (['host', 'database', 'username'] as $key) {
        if (trim((string) ($db[$key] ?? '')) === '') {
            throw new InvalidArgumentException("Database {$key} খালি রাখা যাবে না।");
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        trim((string) $db['host']),
        (int) ($db['port'] ?? 3306) ?: 3306,
        trim((string) $db['database'])
    );

    try {
        return new PDO($dsn, trim((string) $db['username']), (string) ($db['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 8,
        ]);
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, 'Access denied')) {
            $msg = 'Username বা Password ভুল, অথবা এই ইউজারকে ডাটাবেসে যোগ করা হয়নি (cPanel → MySQL Databases → Add User To Database → ALL PRIVILEGES)।';
        } elseif (str_contains($msg, 'Unknown database')) {
            $msg = 'এই নামে কোনো ডাটাবেস নেই। cPanel-এ ডাটাবেসের পুরো নাম দিন (যেমন cpaneluser_brownfoxit)।';
        } elseif (str_contains($msg, 'getaddrinfo') || str_contains($msg, '[2002]') || str_contains($msg, 'Connection refused') || str_contains($msg, 'timed out')) {
            $msg = 'ডাটাবেস সার্ভারে যুক্ত হওয়া যাচ্ছে না। cPanel-এ সাধারণত Host হয় "localhost"।';
        }
        throw new RuntimeException($msg);
    }
}

function bfi_validate_admin(array $a): array
{
    $name = trim((string) ($a['name'] ?? ''));
    $email = trim((string) ($a['email'] ?? ''));
    $password = (string) ($a['password'] ?? '');

    if ($name === '') {
        throw new InvalidArgumentException('Admin-এর নাম দিন।');
    }
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('সঠিক ইমেইল ঠিকানা দিন।');
    }
    if (strlen($password) < 8) {
        throw new InvalidArgumentException('পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।');
    }

    return compact('name', 'email', 'password');
}

/** The seeder reads ADMIN_* from the environment; real env vars win over .env. */
function bfi_admin_env(array $a): void
{
    $admin = bfi_validate_admin($a);
    foreach (['ADMIN_NAME' => $admin['name'], 'ADMIN_EMAIL' => $admin['email'], 'ADMIN_PASSWORD' => $admin['password']] as $k => $v) {
        putenv("{$k}={$v}");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}

function bfi_env_quote(string $value): string
{
    $value = str_replace(["\r", "\n"], '', $value);

    // Single quotes are taken literally by phpdotenv (no ${VAR} expansion, no escapes).
    if (! str_contains($value, "'")) {
        return "'".$value."'";
    }

    return '"'.str_replace(['\\', '"', '${'], ['\\\\', '\\"', '\\${'], $value).'"';
}

function bfi_env_value(string $key): ?string
{
    $file = BFI_ROOT.'/.env';
    if (! is_file($file)) {
        return null;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=\s*(.*)$/', $line, $m)) {
            $v = trim($m[1]);
            if (strlen($v) >= 2 && $v[0] === "'" && str_ends_with($v, "'")) {
                $v = substr($v, 1, -1);
            } elseif (strlen($v) >= 2 && $v[0] === '"' && str_ends_with($v, '"')) {
                $v = str_replace(['\\${', '\\"', '\\\\'], ['${', '"', '\\'], substr($v, 1, -1));
            }

            return $v;
        }
    }

    return null;
}

/** Writes .env from .env.example with the installer's answers. Returns the deploy token. */
function bfi_write_env(array $in): string
{
    $db = $in['db'] ?? [];
    $site = $in['site'] ?? [];

    $url = rtrim(trim((string) ($site['url'] ?? bfi_guess_url())), '/');
    if (! filter_var($url, FILTER_VALIDATE_URL)) {
        throw new InvalidArgumentException('Website URL সঠিক নয় (যেমন https://www.brownfoxit.com)।');
    }

    // Keep an existing APP_KEY / DEPLOY_TOKEN so re-running never logs everyone out.
    $key = bfi_env_value('APP_KEY') ?: 'base64:'.base64_encode(random_bytes(32));
    $token = bfi_env_value('DEPLOY_TOKEN') ?: bin2hex(random_bytes(20));

    $values = [
        'APP_NAME' => bfi_env_quote(trim((string) ($site['name'] ?? 'Brown Fox IT')) ?: 'Brown Fox IT'),
        'APP_ENV' => 'production',
        'APP_KEY' => $key,
        'APP_DEBUG' => 'false',
        'APP_URL' => $url,
        'LOG_LEVEL' => 'error',
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => trim((string) ($db['host'] ?? 'localhost')),
        'DB_PORT' => (string) ((int) ($db['port'] ?? 3306) ?: 3306),
        'DB_DATABASE' => trim((string) $db['database']),
        'DB_USERNAME' => trim((string) $db['username']),
        'DB_PASSWORD' => bfi_env_quote((string) ($db['password'] ?? '')),
        'MAIL_FROM_ADDRESS' => bfi_env_quote(trim((string) ($in['admin']['email'] ?? 'hello@example.com'))),
        'ADMIN_NAME' => '""',
        'ADMIN_EMAIL' => '""',
        'ADMIN_PASSWORD' => '""', // never store the admin password in .env
        'DEPLOY_TOKEN' => $token,
        'SITE_IMAGE_SOURCE' => bfi_env_quote(trim((string) ($site['image_source'] ?? '')) ?: 'https://www.brownfoxit.com/wp-content/'),
    ];

    $template = is_file(BFI_ROOT.'/.env.example') ? (string) file_get_contents(BFI_ROOT.'/.env.example') : '';
    $lines = preg_split('/\R/', $template) ?: [];
    $seen = [];

    foreach ($lines as $i => $line) {
        if (preg_match('/^\s*([A-Z0-9_]+)\s*=/', $line, $m) && array_key_exists($m[1], $values)) {
            $lines[$i] = $m[1].'='.$values[$m[1]];
            $seen[$m[1]] = true;
        }
    }
    foreach ($values as $k => $v) {
        if (! isset($seen[$k])) {
            $lines[] = "{$k}={$v}";
        }
    }

    if (@file_put_contents(BFI_ROOT.'/.env', implode("\n", $lines)."\n") === false) {
        throw new RuntimeException('.env ফাইল লেখা যায়নি। প্রজেক্ট ফোল্ডারের permission দেখুন।');
    }
    @chmod(BFI_ROOT.'/.env', 0640);

    // Old cached config would ignore the new .env.
    foreach (['config.php', 'routes-v7.php', 'events.php'] as $cache) {
        @unlink(BFI_ROOT.'/bootstrap/cache/'.$cache);
    }

    return $token;
}

function bfi_boot(): void
{
    static $booted = false;
    if ($booted) {
        return;
    }
    if (! is_file(BFI_ROOT.'/vendor/autoload.php')) {
        throw new RuntimeException('vendor ফোল্ডার পাওয়া যায়নি।');
    }
    if (! is_file(BFI_ROOT.'/.env')) {
        throw new RuntimeException('.env ফাইল নেই। আগের ধাপটা আবার চালান।');
    }

    require_once BFI_ROOT.'/vendor/autoload.php';
    $app = require BFI_ROOT.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $booted = true;
}

$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
http_response_code(200);
header('Cache-Control: no-store');
?><!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Brown Fox IT – Installer</title>
<link rel="stylesheet" href="<?= $h($base) ?>/vendor/fonts/fonts.css">
<link rel="stylesheet" href="<?= $h($base) ?>/vendor/fontawesome/css/all.min.css">
<style>
:root{--grad:linear-gradient(90deg,#ff9a3e,#ff5266);--bg:#140438;--card:#1d1047;--line:rgba(255,255,255,.1);--text:#c9c8cd;--ok:#1dae22;--bad:#ff5266}
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;font-family:"Lato","Noto Sans Bengali","Hind Siliguri",sans-serif;color:var(--text);background:radial-gradient(1200px 600px at 10% 0%,#3b1a7a 0%,transparent 60%),radial-gradient(900px 500px at 100% 100%,#6b1f5c 0%,transparent 55%),var(--bg);display:flex;align-items:center;justify-content:center;padding:30px 16px;overflow-x:hidden}
.dots span{position:fixed;border-radius:50%;opacity:.7;animation:drift linear infinite}
@keyframes drift{from{transform:translate(0,-10vh)}to{transform:translate(-30vw,110vh)}}
.overlay{position:fixed;inset:0;background:rgba(10,4,30,.55);backdrop-filter:blur(6px)}
.modal{position:relative;z-index:2;width:100%;max-width:640px;background:var(--card);border:1px solid var(--line);border-radius:24px;box-shadow:0 40px 80px rgba(0,0,0,.5);overflow:hidden;animation:pop .45s cubic-bezier(.2,.9,.3,1.2)}
@keyframes pop{from{opacity:0;transform:translateY(30px) scale(.96)}to{opacity:1;transform:none}}
.head{padding:28px 32px 20px;border-bottom:1px solid var(--line)}
.brand{font-family:"Inter Tight",sans-serif;font-weight:800;font-size:26px;color:#fff}
.brand b{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.head p{font-size:14px;margin-top:4px}
.steps{display:flex;gap:6px;margin-top:20px}
.steps i{flex:1;height:5px;border-radius:5px;background:rgba(255,255,255,.12);transition:.4s}
.steps i.on{background:var(--grad)}
.body{padding:26px 32px 30px}
h2{font-family:"Inter Tight",sans-serif;color:#fff;font-size:21px;margin-bottom:6px}
.sub{font-size:14px;margin-bottom:20px}
.panel{display:none}.panel.active{display:block;animation:fade .35s}
@keyframes fade{from{opacity:0;transform:translateX(15px)}to{opacity:1;transform:none}}
.checks{list-style:none;max-height:330px;overflow:auto;border:1px solid var(--line);border-radius:14px}
.checks li{display:flex;gap:12px;padding:10px 14px;border-bottom:1px solid var(--line);font-size:14px}
.checks li:last-child{border-bottom:0}
.checks .ic{width:22px;flex-shrink:0;text-align:center}
.checks .ok .ic{color:var(--ok)}.checks .bad .ic{color:var(--bad)}
.checks small{display:block;color:#ffb3bc;margin-top:2px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.grid .full{grid-column:1/-1}
label{display:block;font-size:13px;color:#fff;font-weight:700;margin-bottom:6px}
input{width:100%;height:46px;border-radius:12px;border:1px solid var(--line);background:#120a33;color:#fff;padding:0 14px;font-size:15px;font-family:inherit;outline:0;transition:.2s}
input:focus{border-color:#ff7a59;box-shadow:0 0 0 3px rgba(255,90,90,.15)}
.hint{font-size:12px;margin-top:5px;color:#9a94b8}
.check{display:flex;gap:10px;align-items:flex-start;font-size:14px;margin-top:6px}
.check input{width:18px;height:18px;margin-top:2px;accent-color:#ff5266}
.actions{display:flex;justify-content:space-between;align-items:center;margin-top:24px;gap:12px}
.btn{display:inline-flex;align-items:center;gap:8px;height:48px;padding:0 28px;border-radius:50px;border:0;cursor:pointer;font-family:"Inter Tight",sans-serif;font-weight:700;font-size:15px;color:#fff;background:var(--grad);box-shadow:0 10px 25px rgba(255,82,102,.3);transition:.2s}
.btn:hover{transform:translateY(-2px)}.btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.btn.ghost{background:transparent;box-shadow:none;border:1px solid var(--line)}
.msg{margin-top:16px;padding:12px 14px;border-radius:12px;font-size:14px;display:none}
.msg.show{display:block}.msg.err{background:rgba(255,82,102,.12);color:#ffb3bc;border:1px solid rgba(255,82,102,.35)}
.msg.good{background:rgba(29,174,34,.12);color:#9fe3a2;border:1px solid rgba(29,174,34,.35)}
.log{list-style:none}
.log li{display:flex;gap:12px;align-items:center;padding:11px 0;border-bottom:1px solid var(--line);font-size:15px}
.log li .ic{width:24px;text-align:center}
.log li.wait{opacity:.45}.log li.run .ic{color:#ff9a3e}.log li.done .ic{color:var(--ok)}.log li.fail .ic{color:var(--bad)}
.log small{margin-left:auto;font-size:12px;color:#9a94b8}
.done-ic{width:84px;height:84px;border-radius:50%;background:var(--grad);display:grid;place-items:center;font-size:38px;color:#fff;margin:4px auto 18px;box-shadow:0 15px 40px rgba(255,82,102,.4)}
.center{text-align:center}
.token{display:flex;gap:8px;margin-top:8px}.token input{font-family:monospace;font-size:13px}
.token button{flex-shrink:0;height:46px;padding:0 16px;border-radius:12px;border:1px solid var(--line);background:#2a1b5e;color:#fff;cursor:pointer}
.box{border:1px solid var(--line);border-radius:14px;padding:16px;margin-top:20px;text-align:left;font-size:14px}
.box b{color:#fff}
@media(max-width:560px){.grid{grid-template-columns:1fr}.head,.body{padding-left:20px;padding-right:20px}.actions{flex-direction:column-reverse}.actions .btn{width:100%;justify-content:center}}
</style>
</head>
<body>
<div class="dots" id="dots"></div>
<div class="overlay"></div>

<div class="modal" role="dialog" aria-modal="true" aria-labelledby="ttl">
  <div class="head">
    <div class="brand">Brown <b>Fox</b> IT</div>
    <p>ওয়েবসাইট Installer — কয়েকটা ধাপে সাইট চালু করুন</p>
    <div class="steps" id="bar"><i class="on"></i><i></i><i></i><i></i><i></i></div>
  </div>

  <div class="body">
    <!-- 1. Requirements -->
    <section class="panel active" data-step="1">
      <h2 id="ttl">১. সার্ভার পরীক্ষা</h2>
      <p class="sub">আপনার হোস্টিং-এ সাইট চালানোর জন্য যা যা লাগে, সেগুলো আছে কি না দেখা হচ্ছে।</p>
      <ul class="checks" id="checks"><li><span class="ic"><i class="fa-solid fa-spinner fa-spin"></i></span>পরীক্ষা চলছে…</li></ul>
      <div class="msg" id="msg1"></div>
      <div class="actions">
        <button class="btn ghost" id="recheck"><i class="fa-solid fa-rotate"></i> আবার পরীক্ষা</button>
        <button class="btn" id="next1" disabled>পরের ধাপ <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </section>

    <!-- 2. Database -->
    <section class="panel" data-step="2">
      <h2>২. ডাটাবেস</h2>
      <p class="sub">cPanel → <b>MySQL® Databases</b> থেকে একটা ডাটাবেস আর ইউজার বানান, ইউজারকে ডাটাবেসে যোগ করে <b>ALL PRIVILEGES</b> দিন, তারপর তথ্যগুলো এখানে বসান।</p>
      <div class="grid">
        <div><label for="db_host">Database Host</label><input id="db_host" value="localhost"></div>
        <div><label for="db_port">Port</label><input id="db_port" value="3306" inputmode="numeric"></div>
        <div class="full"><label for="db_name">Database Name</label><input id="db_name" placeholder="cpaneluser_brownfoxit" autocomplete="off"></div>
        <div><label for="db_user">Database Username</label><input id="db_user" placeholder="cpaneluser_admin" autocomplete="off"></div>
        <div><label for="db_pass">Database Password</label><input id="db_pass" type="password" autocomplete="new-password"></div>
      </div>
      <div class="msg" id="msg2"></div>
      <div class="actions">
        <button class="btn ghost" data-back>← পেছনে</button>
        <button class="btn" id="next2">সংযোগ পরীক্ষা করুন <i class="fa-solid fa-plug"></i></button>
      </div>
    </section>

    <!-- 3. Site & admin -->
    <section class="panel" data-step="3">
      <h2>৩. ওয়েবসাইট ও Admin অ্যাকাউন্ট</h2>
      <p class="sub">এই ইমেইল আর পাসওয়ার্ড দিয়েই পরে Admin panel-এ লগইন করবেন।</p>
      <div class="grid">
        <div><label for="site_name">ওয়েবসাইটের নাম</label><input id="site_name" value="Brown Fox IT"></div>
        <div><label for="site_url">Website URL</label><input id="site_url" placeholder="https://www.brownfoxit.com"></div>
        <div><label for="ad_name">Admin-এর নাম</label><input id="ad_name" placeholder="Arjun Kumar" autocomplete="name"></div>
        <div><label for="ad_email">Admin ইমেইল</label><input id="ad_email" type="email" placeholder="hello@pabitra.net" autocomplete="email"></div>
        <div><label for="ad_pass">পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label><input id="ad_pass" type="password" autocomplete="new-password"></div>
        <div><label for="ad_pass2">পাসওয়ার্ড আবার লিখুন</label><input id="ad_pass2" type="password" autocomplete="new-password"></div>
        <div class="full">
          <label class="check"><input type="checkbox" id="img_on" checked> <span>পুরোনো সাইট থেকে সব ছবি নামিয়ে আনুন <span class="hint">(ছবি আগেই public/uploads-এ কপি করে থাকলে টিক তুলে দিন)</span></span></label>
          <input id="img_src" value="https://www.brownfoxit.com/wp-content/" style="margin-top:8px">
          <div class="hint">ছবির উৎস: WordPress-এর wp-content ফোল্ডারের ঠিকানা</div>
        </div>
      </div>
      <div class="msg" id="msg3"></div>
      <div class="actions">
        <button class="btn ghost" data-back>← পেছনে</button>
        <button class="btn" id="next3">Install শুরু করুন <i class="fa-solid fa-rocket"></i></button>
      </div>
    </section>

    <!-- 4. Install -->
    <section class="panel" data-step="4">
      <h2>৪. Install হচ্ছে…</h2>
      <p class="sub">এই পেজটা বন্ধ করবেন না।</p>
      <ul class="log" id="log">
        <li data-k="write-env" class="wait"><span class="ic"><i class="fa-regular fa-circle"></i></span>সেটিং ফাইল (.env) তৈরি<small></small></li>
        <li data-k="migrate" class="wait"><span class="ic"><i class="fa-regular fa-circle"></i></span>ডাটাবেস টেবিল তৈরি<small></small></li>
        <li data-k="seed" class="wait"><span class="ic"><i class="fa-regular fa-circle"></i></span>সাইটের কন্টেন্ট যোগ<small></small></li>
        <li data-k="admin" class="wait"><span class="ic"><i class="fa-regular fa-circle"></i></span>Admin অ্যাকাউন্ট তৈরি<small></small></li>
        <li data-k="images" class="wait"><span class="ic"><i class="fa-regular fa-circle"></i></span>ছবি নামানো<small></small></li>
        <li data-k="finish" class="wait"><span class="ic"><i class="fa-regular fa-circle"></i></span>শেষ ধাপ<small></small></li>
      </ul>
      <div class="msg" id="msg4"></div>
      <div class="actions" id="retryRow" style="display:none">
        <button class="btn ghost" data-back>← তথ্য বদলান</button>
        <button class="btn" id="retry"><i class="fa-solid fa-rotate"></i> আবার চেষ্টা</button>
      </div>
    </section>

    <!-- 5. Done -->
    <section class="panel" data-step="5">
      <div class="center">
        <div class="done-ic"><i class="fa-solid fa-check"></i></div>
        <h2>অভিনন্দন! সাইট চালু হয়ে গেছে 🎉</h2>
        <p class="sub">Installer বন্ধ হয়ে গেছে, এটা আর কখনো দেখা যাবে না।</p>
      </div>
      <div class="msg" id="msg5"></div>
      <div class="box">
        <b>GitHub Auto-Deploy-এর জন্য Deploy Token</b>
        <div class="hint">এটা কপি করে GitHub repo → Settings → Secrets → Actions-এ <b>DEPLOY_TOKEN</b> নামে রাখুন। পরে লাগলে সার্ভারের .env ফাইলেও পাবেন।</div>
        <div class="token"><input id="token" readonly><button id="copy"><i class="fa-regular fa-copy"></i></button></div>
      </div>
      <div class="actions" style="justify-content:center">
        <a class="btn" id="visit" href="#">ওয়েবসাইট দেখুন <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
      </div>
    </section>
  </div>
</div>

<script>
(function () {
  var $ = function (id) { return document.getElementById(id); };
  var endpoint = window.location.pathname;
  var step = 1, state = {};

  // floating dots like the site
  var colors = ['#FF6A59', '#B3DB61', '#0081FF', '#FF5337'];
  for (var i = 0; i < 14; i++) {
    var d = document.createElement('span'), s = 3 + Math.random() * 4;
    d.style.cssText = 'width:' + s + 'px;height:' + s + 'px;background:' + colors[i % 4] + ';left:' + (Math.random() * 130) + 'vw;top:-10px;animation-duration:' + (12 + Math.random() * 14) + 's;animation-delay:-' + (Math.random() * 20) + 's';
    $('dots').appendChild(d);
  }

  function go(n) {
    step = n;
    document.querySelectorAll('.panel').forEach(function (p) { p.classList.toggle('active', +p.dataset.step === n); });
    document.querySelectorAll('#bar i').forEach(function (b, idx) { b.classList.toggle('on', idx < n); });
  }
  document.querySelectorAll('[data-back]').forEach(function (b) { b.addEventListener('click', function () { go(step === 4 ? 3 : step - 1); }); });

  function msg(id, text, good) {
    var el = $(id); el.textContent = text || ''; el.className = 'msg' + (text ? ' show ' + (good ? 'good' : 'err') : '');
  }

  function call(action, data) {
    return fetch(endpoint + '?action=' + action, {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(data || {})
    }).then(function (r) {
      return r.text().then(function (t) {
        var j; try { j = JSON.parse(t); } catch (e) { throw new Error('সার্ভার থেকে অপ্রত্যাশিত উত্তর এসেছে (HTTP ' + r.status + '). ' + t.replace(/<[^>]+>/g, ' ').trim().slice(0, 300)); }
        if (!j.ok) throw new Error(j.error || 'কিছু একটা ভুল হয়েছে'); return j;
      });
    });
  }

  // ---- 1. requirements
  function check() {
    $('next1').disabled = true; msg('msg1', '');
    call('requirements').then(function (r) {
      $('checks').innerHTML = r.checks.map(function (c) {
        return '<li class="' + (c.ok ? 'ok' : 'bad') + '"><span class="ic"><i class="fa-solid ' + (c.ok ? 'fa-circle-check' : 'fa-circle-xmark') + '"></i></span><div>' + c.label + (c.ok ? '' : '<small>' + c.help + '</small>') + '</div></li>';
      }).join('');
      $('next1').disabled = !r.passed;
      if (!$('site_url').value) $('site_url').value = r.suggested_url;
      msg('msg1', r.passed ? 'সব ঠিক আছে! পরের ধাপে যান।' : 'লাল চিহ্ন দেওয়া জিনিসগুলো ঠিক করে "আবার পরীক্ষা" চাপুন।', r.passed);
    }).catch(function (e) { msg('msg1', e.message); });
  }
  $('recheck').addEventListener('click', check);
  $('next1').addEventListener('click', function () { go(2); });
  check();

  // ---- 2. database
  function db() { return { host: $('db_host').value, port: $('db_port').value, database: $('db_name').value, username: $('db_user').value, password: $('db_pass').value }; }
  $('next2').addEventListener('click', function () {
    var b = this; b.disabled = true; msg('msg2', '');
    call('test-db', db()).then(function (r) {
      state.db = db();
      msg('msg2', 'সংযোগ সফল! (MySQL ' + r.version + (r.tables ? ', ' + r.tables + 'টা টেবিল আগে থেকেই আছে' : '') + ')', true);
      setTimeout(function () { go(3); }, 700);
    }).catch(function (e) { msg('msg2', e.message); }).then(function () { b.disabled = false; });
  });

  // ---- 3. site + admin
  $('next3').addEventListener('click', function () {
    msg('msg3', '');
    if ($('ad_pass').value.length < 8) return msg('msg3', 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।');
    if ($('ad_pass').value !== $('ad_pass2').value) return msg('msg3', 'দুটো পাসওয়ার্ড মেলেনি।');
    if (!/^\S+@\S+\.\S+$/.test($('ad_email').value)) return msg('msg3', 'সঠিক ইমেইল দিন।');
    if (!$('ad_name').value.trim()) return msg('msg3', 'Admin-এর নাম দিন।');
    state.site = { name: $('site_name').value, url: $('site_url').value, image_source: $('img_src').value };
    state.admin = { name: $('ad_name').value, email: $('ad_email').value, password: $('ad_pass').value };
    state.images = $('img_on').checked;
    go(4); install();
  });

  // ---- 4. install
  function row(k, cls, note) {
    var li = document.querySelector('#log li[data-k="' + k + '"]'); li.className = cls;
    li.querySelector('.ic').innerHTML = { run: '<i class="fa-solid fa-spinner fa-spin"></i>', done: '<i class="fa-solid fa-circle-check"></i>', fail: '<i class="fa-solid fa-circle-xmark"></i>', wait: '<i class="fa-regular fa-circle"></i>' }[cls];
    if (note !== undefined) li.querySelector('small').textContent = note;
  }

  function images(skip, done, failed) {
    return call('images', { source: state.site.image_source, skip: skip }).then(function (r) {
      done += r.done; failed = failed.concat(r.failed);
      row('images', 'run', done + 'টা নামানো হয়েছে, বাকি ' + r.left);
      if (r.left > 0 && (r.done > 0 || r.failed.length > 0)) return images(skip + r.failed.length, done, failed);
      return { done: done, failed: failed };
    });
  }

  function install() {
    $('retryRow').style.display = 'none'; msg('msg4', '');
    document.querySelectorAll('#log li').forEach(function (li) { row(li.dataset.k, 'wait', ''); });
    var payload = { db: state.db, site: state.site, admin: state.admin };
    var steps = ['write-env', 'migrate', 'seed', 'admin'];
    var chain = Promise.resolve();
    steps.forEach(function (k) {
      chain = chain.then(function () { row(k, 'run'); return call(k, payload).then(function () { row(k, 'done'); }); })
        .catch(function (e) { if (!e.step) { row(k, 'fail'); e.step = k; } throw e; });
    });
    chain.then(function () {
      if (!state.images) { row('images', 'done', 'বাদ দেওয়া হয়েছে'); return; }
      row('images', 'run');
      return images(0, 0, []).then(function (r) {
        row('images', 'done', r.done + 'টা ছবি' + (r.failed.length ? ', ' + r.failed.length + 'টা পাওয়া যায়নি' : ''));
        if (r.failed.length) console.warn('Images not downloaded:', r.failed);
      }).catch(function (e) { row('images', 'fail', 'পরে "php artisan site:download-images" চালান'); console.warn(e); });
    }).then(function () {
      row('finish', 'run');
      return call('finish').then(function (r) {
        row('finish', 'done');
        $('token').value = r.deploy_token; $('visit').href = r.url || '/';
        setTimeout(function () { go(5); }, 600);
      }).catch(function (e) { row('finish', 'fail'); e.step = 'finish'; throw e; });
    }).catch(function (e) {
      msg('msg4', e.message); $('retryRow').style.display = 'flex';
    });
  }
  $('retry').addEventListener('click', install);

  $('copy').addEventListener('click', function () {
    var t = $('token'); t.select();
    (navigator.clipboard ? navigator.clipboard.writeText(t.value) : Promise.reject()).catch(function () { document.execCommand('copy'); });
    this.innerHTML = '<i class="fa-solid fa-check"></i>';
  });
})();
</script>
</body>
</html>
