<?php
/**
 * Auto-deploy helper: swaps in a new vendor/ folder.
 *
 * Uploading Laravel's ~7,000 library files one by one over FTP is very slow,
 * so GitHub Actions uploads them as ONE file (.deploy/vendor.zip) together with
 * its fingerprint (.deploy/vendor.hash), then calls:
 *
 *   POST /_deploy-vendor.php   header X-Deploy-Token: <DEPLOY_TOKEN from .env>
 *
 * If the fingerprint differs from the installed one, the zip is extracted to
 * vendor_new/ and swapped with vendor/ (old one deleted). Plain PHP on purpose:
 * it must not load Laravel from the vendor folder it is replacing.
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

$root = dirname(__DIR__);

function bfi_out(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function bfi_env(string $root, string $key): string
{
    foreach (@file($root.'/.env', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=\s*(.*)$/', $line, $m)) {
            return trim(trim($m[1]), '"\'');
        }
    }

    return '';
}

function bfi_rmdir(string $dir): void
{
    if (! is_dir($dir) || is_link($dir)) {
        @unlink($dir);

        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() && ! $f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    bfi_out(404, ['ok' => false]);
}

$token = bfi_env($root, 'DEPLOY_TOKEN');
$given = (string) ($_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '');
if ($token === '' || ! hash_equals($token, $given)) {
    bfi_out(404, ['ok' => false]);
}

@set_time_limit(300);
@ignore_user_abort(true);

$zipFile = $root.'/.deploy/vendor.zip';
$hashFile = $root.'/.deploy/vendor.hash';
$installed = $root.'/storage/vendor.hash';

$newHash = trim((string) @file_get_contents($hashFile));
$oldHash = trim((string) @file_get_contents($installed));

if ($newHash === '' || ! is_file($zipFile)) {
    bfi_out(200, ['ok' => true, 'vendor' => 'no bundle uploaded, nothing to do']);
}
if ($newHash === $oldHash && is_file($root.'/vendor/autoload.php')) {
    bfi_out(200, ['ok' => true, 'vendor' => 'unchanged']);
}
if (! class_exists(ZipArchive::class)) {
    bfi_out(500, ['ok' => false, 'error' => 'PHP zip extension is missing (cPanel → Select PHP Version → Extensions → zip)']);
}

$new = $root.'/vendor_new';
$old = $root.'/vendor_old';
bfi_rmdir($new);
bfi_rmdir($old);

$zip = new ZipArchive();
if ($zip->open($zipFile) !== true) {
    bfi_out(500, ['ok' => false, 'error' => 'could not open vendor.zip']);
}
// The zip contains "vendor/..." entries: extract next to it, then move.
$tmp = $root.'/vendor_tmp';
bfi_rmdir($tmp);
if (! $zip->extractTo($tmp)) {
    $zip->close();
    bfi_rmdir($tmp);
    bfi_out(500, ['ok' => false, 'error' => 'could not extract vendor.zip (disk space / permissions?)']);
}
$zip->close();
bfi_rmdir($new);
if (! @rename($tmp.'/vendor', $new) || ! is_file($new.'/autoload.php')) {
    bfi_rmdir($tmp);
    bfi_rmdir($new);
    bfi_out(500, ['ok' => false, 'error' => 'vendor.zip has no vendor/autoload.php']);
}
bfi_rmdir($tmp);

// Swap: two renames, so the site is without vendor/ only for a moment.
if (is_dir($root.'/vendor') && ! @rename($root.'/vendor', $old)) {
    bfi_rmdir($new);
    bfi_out(500, ['ok' => false, 'error' => 'could not move the old vendor folder']);
}
if (! @rename($new, $root.'/vendor')) {
    @rename($old, $root.'/vendor'); // put the old one back
    bfi_out(500, ['ok' => false, 'error' => 'could not move the new vendor folder into place']);
}
bfi_rmdir($old);

@file_put_contents($installed, $newHash);
foreach (glob($root.'/bootstrap/cache/*.php') ?: [] as $cache) {
    @unlink($cache); // package / service caches belong to the old vendor
}
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

bfi_out(200, ['ok' => true, 'vendor' => 'updated', 'hash' => $newHash]);
