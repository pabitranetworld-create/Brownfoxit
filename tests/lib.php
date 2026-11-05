<?php
/** Shared helpers for the browser-like end-to-end tests. */

$root = dirname(__DIR__);
$base = rtrim($base ?? 'http://127.0.0.1:8000', '/');
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

function ok_page(array $p, string $msg): bool
{
    $good = $p['code'] === 200 && ! str_contains($p['body'], 'Whoops') && ! str_contains($p['body'], 'Server Error');
    ok($good, $msg.($good ? '' : " (HTTP {$p['code']})"));
    if (! $good) {
        echo substr(strip_tags($p['body']), 0, 1500)."\n";
    }

    return $good;
}

