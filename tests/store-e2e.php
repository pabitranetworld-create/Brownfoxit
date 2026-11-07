<?php
/**
 * End-to-end test of phase 3: shop (products, variants, coupons, cart, checkout, manual payment,
 * admin confirmation, secure downloads, refunds), the payment gateway drivers (with faked HTTP),
 * the no-code custom gateway, portfolio/blog pages and the WordPress importer.
 *
 *   php tests/store-e2e.php http://127.0.0.1:8000 admin@example.com 'Secret@12345'
 * Run from the application folder (it boots Laravel for setup and the faked-gateway checks).
 */

[$script, $base, $email, $password] = $argv + [null, 'http://127.0.0.1:8000', 'admin@example.com', 'Secret@12345'];
require __DIR__.'/lib.php';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Coupon;
use App\Models\DownloadPermission;
use App\Models\ImportLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Store\Gateways;
use App\Store\OrderService;
use App\Support\WordPressImporter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function text(string $html): string
{
    return preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($html, ENT_QUOTES)));
}

$tag = Str::lower(Str::random(5));

echo "== Setup: products, coupon, manual gateway\n";
$dir = storage_path('app/private/products/e2e-'.$tag);
@mkdir($dir, 0775, true);
file_put_contents($dir.'/guide.pdf', '%PDF-1.4 e2e guide '.$tag);
file_put_contents($dir.'/pro.zip', 'PK pro files '.$tag);
file_put_contents($dir.'/basic.zip', 'PK basic files '.$tag);
$rel = 'products/e2e-'.$tag;

$a = Product::create(['name' => 'E2E Guide '.$tag, 'slug' => 'e2e-guide-'.$tag, 'status' => 'published', 'type' => 'digital', 'price' => 1000, 'sale_price' => 800,
    'short_description' => '<p>Short</p>', 'description' => '<p>Long description</p>',
    'files' => [['key' => 'g1', 'name' => 'Guide PDF', 'path' => $rel.'/guide.pdf', 'variant' => '']]]);
$b = Product::create(['name' => 'E2E Kit '.$tag, 'slug' => 'e2e-kit-'.$tag, 'status' => 'published', 'type' => 'digital', 'price' => 500,
    'variants' => [['key' => 'basic', 'name' => 'Basic', 'price' => 500], ['key' => 'pro', 'name' => 'Pro', 'price' => 900, 'sale_price' => 850]],
    'files' => [['key' => 'fb', 'name' => 'Basic files', 'path' => $rel.'/basic.zip', 'variant' => 'basic'], ['key' => 'fp', 'name' => 'Pro files', 'path' => $rel.'/pro.zip', 'variant' => 'pro']]]);
$c = Product::create(['name' => 'E2E Draft '.$tag, 'slug' => 'e2e-draft-'.$tag, 'status' => 'draft', 'type' => 'digital', 'price' => 50]);
Coupon::create(['code' => 'E2E'.strtoupper($tag), 'type' => 'percent', 'value' => 10, 'is_active' => true]);
Gateways::save('manual_bank', ['enabled' => true, 'title' => 'Bank transfer', 'description' => 'Pay to our bank', 'sort' => 1, 'instructions' => 'Send the money to account 123. Use your order number as reference.', 'ask_reference' => true, 'reference_label' => 'Transaction ID']);
Cache::flush();
ok(Gateways::driver('manual_bank')->enabled() && Gateways::driver('manual_bank')->configured(), 'manual gateway enabled');
ok(! Gateways::driver('paystation')->enabled() || Gateways::driver('paystation')->configured(), 'unconfigured gateways stay off');

echo "== Shop pages\n";
$shop = get('/shop');
ok($shop['code'] === 200 && str_contains($shop['body'], 'E2E Guide '.$tag) && str_contains($shop['body'], 'E2E Kit '.$tag), '/shop lists published products');
ok(! str_contains($shop['body'], 'E2E Draft '.$tag), 'draft product hidden from shop');
ok(get('/shop/e2e-draft-'.$tag)['code'] === 404, 'draft product page is 404 for visitors');
$pa = get('/shop/e2e-guide-'.$tag);
ok($pa['code'] === 200 && str_contains(text($pa['body']), '800') && str_contains($pa['body'], '"@type":"Product"'), 'product page shows sale price + Product schema');
$pb = get('/shop/e2e-kit-'.$tag);
ok(str_contains($pb['body'], 'name="variant"') && str_contains(text($pb['body']), 'Pro'), 'variant product shows its options');

echo "== Cart, coupon and checkout (guest, manual payment)\n";
$f = form($pa['body'], '/cart/add');
$r = submit($f['action'], $f['fields']);
ok(in_array($r['code'], [302, 303], true), 'add to cart');
$f = form($pb['body'], '/cart/add');
set($f['fields'], 'variant', 'pro');
submit($f['action'], $f['fields']);
$cart = get('/cart');
ok(str_contains(text($cart['body']), 'E2E Guide '.$tag) && str_contains(text($cart['body']), 'Pro'), 'cart shows both items with the variant');
$f = form($cart['body'], '/cart/coupon');
set($f['fields'], 'code', 'E2E'.strtoupper($tag));
follow(submit($f['action'], $f['fields']));
$f = form(get('/cart')['body'], '/cart/coupon');
$f = form(get('/cart')['body'], '/cart/coupon');
set($f['fields'], 'code', 'NOPE-'.$tag);
$bad = follow(submit($f['action'], $f['fields']));
ok(str_contains(text($bad['body']), 'oupon'), 'unknown coupon shows an error');
$f = form(get('/cart')['body'], '/cart/coupon');
set($f['fields'], 'code', 'E2E'.strtoupper($tag));
follow(submit($f['action'], $f['fields']));

$co = get('/checkout');
ok($co['code'] === 200 && str_contains($co['body'], 'value="manual_bank"'), 'checkout offers the enabled gateway');
ok(! str_contains($co['body'], 'value="paystation"') || Gateways::driver('paystation')->enabled(), 'disabled gateway is not offered');
$f = form($co['body'], '/checkout');
foreach (['name' => 'E2E Buyer', 'email' => "buyer-{$tag}@example.com", 'phone' => '01700000000', 'address' => 'Road 1', 'city' => 'Dhaka', 'country' => 'Bangladesh', 'gateway' => 'manual_bank', 'reference' => 'TRX'.$tag, 'terms' => '1'] as $k => $v) {
    set($f['fields'], $k, $v);
}
set($f['fields'], 'total', '1'); // tampering attempt: ignored, totals are computed on the server
$placed = submit($f['action'], $f['fields']);
ok($placed['code'] === 302 && str_contains((string) $placed['location'], '/order/'), 'order placed → order page');
preg_match('#/order/([A-Za-z0-9\-]+)#', (string) $placed['location'], $mm);
$order = Order::where('number', $mm[1] ?? '-')->first();
$expected = round((800 + 850) * 0.9, 2);
ok($order && abs((float) $order->total - $expected) < 0.01, "server-side total {$expected} (got ".($order->total ?? '-').')');
ok($order && $order->payment_status !== 'paid' && $order->downloads()->count() === 0, 'no downloads before payment');
$op = follow($placed);
ok($op['code'] === 200 && str_contains(text($op['body']), $order->number) && str_contains(text($op['body']), 'Send the money to account 123'), 'order page shows number + payment instructions');
ok(str_contains((string) $order->manual_reference.json_encode($order->payments()->first()?->meta), 'TRX'.$tag), 'customer reference saved');
$jarBackup = $jar; $jar = tempnam(sys_get_temp_dir(), 'jar');
ok(in_array(get('/order/'.$order->number)['code'], [403, 404], true), 'stranger cannot open the order without the key');
ok(get(parse_url($order->link(), PHP_URL_PATH).'?'.parse_url($order->link(), PHP_URL_QUERY))['code'] === 200, 'order link with access key works');
$noCsrf = http('POST', '/checkout', 'name=x', ['Content-Type: application/x-www-form-urlencoded']);
ok($noCsrf['code'] === 419, 'checkout without CSRF token is refused');
$jar = $jarBackup;

echo "== Admin: confirm payment → downloads\n";
$p = get('/admin/login');
if ($p['code'] !== 302) {
    $lf = form($p['body'], '/admin/login');
    set($lf['fields'], 'email', $email);
    set($lf['fields'], 'password', $password);
    submit($lf['action'], $lf['fields']);
}
foreach (['/admin/orders', '/admin/orders/'.$order->id, '/admin/payments', '/admin/payments/aamarpay', '/admin/payments/custom_1', '/admin/customers', '/admin/import', '/admin/content/products', '/admin/content/products/create', '/admin/content/coupons', '/admin/settings/store', '/admin/settings/store_emails', '/admin/settings/portfolio_page'] as $u) {
    ok(get($u)['code'] === 200, "admin page {$u}");
}
$show = get('/admin/orders/'.$order->id);
$uf = form($show['body'], '/admin/orders/'.$order->id);
set($uf['fields'], 'status', 'completed');
follow(submit($uf['action'], $uf['fields']));
ok($order->fresh()->status !== 'completed', 'cannot complete an unpaid order');
$cf = form($show['body'], '/confirm');
follow(submit($cf['action'], $cf['fields']));
$order->refresh();
ok($order->isPaid() && $order->paid_at !== null, 'admin confirmation marks the order paid');
$downloads = $order->downloads()->get();
ok($downloads->count() === 2, 'two downloads granted (guide + pro variant only), got '.$downloads->count());
ok(! $downloads->contains(fn ($d) => str_contains((string) $d->file_name, 'Basic')), 'files of the other variant are not granted');
$again = app(OrderService::class)->confirmManual($order->fresh());
ok($again->downloads()->count() === 2, 'confirming twice does not duplicate anything');

echo "== Secure downloads\n";
$d = $downloads->first();
$jarBackup = $jar; $jar = tempnam(sys_get_temp_dir(), 'jar'); // a link works without logging in
$dl = get('/download/'.$d->token);
ok($dl['code'] === 200 && str_contains($dl['body'], $tag), 'download link delivers the file');
ok(get('/download/'.Str::random(48))['code'] === 404, 'random token → 404');
$d->update(['downloads_remaining' => 0]);
ok(get('/download/'.$d->token)['code'] !== 200, 'download limit enforced');
$d->update(['downloads_remaining' => 3, 'expires_at' => now()->subDay()]);
ok(get('/download/'.$d->token)['code'] !== 200, 'expired link refused');
$d->update(['expires_at' => now()->addDay()]);
$jar = $jarBackup;
$show = get('/admin/orders/'.$order->id);
$uf = form($show['body'], '/admin/orders/'.$order->id);
set($uf['fields'], 'status', 'refunded');
follow(submit($uf['action'], $uf['fields']));
ok($order->fresh()->status === 'refunded' && get('/download/'.$d->token)['code'] !== 200, 'refund revokes downloads');

echo "== Gateway drivers (faked HTTP)\n";
$svc = app(OrderService::class);
$mk = function (float $total) use ($tag) {
    $o = Order::create(['number' => 'E2E-'.Str::upper(Str::random(8)), 'access_key' => Str::random(32), 'status' => 'pending', 'payment_status' => 'unpaid', 'currency' => 'BDT',
        'subtotal' => $total, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => $total,
        'billing' => ['name' => 'GW Test', 'email' => "gw-{$tag}@example.com", 'phone' => '01700000000', 'address' => 'Road 1', 'city' => 'Dhaka', 'country' => 'Bangladesh']]);

    return $o;
};

Gateways::save('aamarpay', ['enabled' => true, 'mode' => 'sandbox', 'store_id' => 'aamarpaytest', 'signature_key' => 'dbb74894e82415a2f7ff0ec3a97e4183']);
$o = $mk(1500);
$pay = $svc->newPayment($o, 'aamarpay');
Http::fake([
    'sandbox.aamarpay.com/jsonpost.php' => Http::response(['result' => 'true', 'payment_url' => 'https://sandbox.aamarpay.com/paynow.php?track=1']),
    'sandbox.aamarpay.com/api/v1/trxcheck/*' => Http::response(['pay_status' => 'Successful', 'pg_txnid' => 'AAM'.$tag, 'mer_txnid' => $pay->reference, 'amount' => '1500.00', 'currency' => 'BDT', 'payment_type' => 'bKash']),
]);
$g = Gateways::driver('aamarpay');
ok(($g->start($o, $pay)['redirect'] ?? '') === 'https://sandbox.aamarpay.com/paynow.php?track=1', 'aamarPay: payment link created');
$v = $g->verify($pay);
ok($v['status'] === 'success' && $v['txn_id'] === 'AAM'.$tag, 'aamarPay: verified with the transaction check API');
$svc->markPaid($pay, $v['txn_id'], $v['amount'], $v['method']);
ok($o->fresh()->isPaid(), 'aamarPay: order paid');
$svc->markPaid($pay->fresh(), $v['txn_id'], $v['amount'], $v['method']);
ok(Payment::where('gateway_txn_id', 'AAM'.$tag)->count() === 1, 'aamarPay: repeated callback is harmless');

$o2 = $mk(2000);
$pay2 = $svc->newPayment($o2, 'aamarpay');
try {
    $svc->markPaid($pay2, 'AAMLOW'.$tag, 20.0);
} catch (Throwable $e) {
}
ok(! $o2->fresh()->isPaid(), 'aamarPay: wrong (smaller) amount is not accepted');

Gateways::save('paystation', ['enabled' => true, 'mode' => 'live', 'merchant_id' => '104-1653730183', 'password' => 'secret']);
$o3 = $mk(999.5);
$pay3 = $svc->newPayment($o3, 'paystation');
Http::fake([
    'api.paystation.com.bd/initiate-payment' => Http::response(['status_code' => '200', 'status' => 'success', 'payment_url' => 'https://api.paystation.com.bd/checkout/abc']),
    'api.paystation.com.bd/transaction-status' => Http::response(['status_code' => '200', 'data' => ['invoice_number' => $pay3->reference, 'trx_status' => 'success', 'trx_id' => 'PS'.$tag, 'payment_amount' => '1000', 'payment_method' => 'bKash']]),
]);
$gp = Gateways::driver('paystation');
ok(($gp->start($o3, $pay3)['redirect'] ?? '') === 'https://api.paystation.com.bd/checkout/abc', 'PayStation: payment link created');
ok(Http::recorded(fn ($r) => str_contains($r->url(), 'initiate-payment') && $r['payment_amount'] == 1000 && $r['invoice_number'] === $pay3->reference)->isNotEmpty(), 'PayStation: amount sent as whole taka + invoice reference');
$v3 = $gp->verify($pay3);
ok($v3['status'] === 'success' && $v3['txn_id'] === 'PS'.$tag, 'PayStation: verified with /transaction-status');

Gateways::save('eps', ['enabled' => true, 'mode' => 'sandbox', 'merchant_id' => 'm-1', 'store_id' => 's-1', 'username' => 'user@example.com', 'password' => 'pw', 'hash_key' => 'HASHKEY']);
$o4 = $mk(300);
$pay4 = $svc->newPayment($o4, 'eps');
Http::fake([
    'sandboxpgapi.eps.com.bd/v1/Auth/GetToken' => Http::response(['token' => 'TOKEN123']),
    'sandboxpgapi.eps.com.bd/v1/EPSEngine/InitializeEPS' => Http::response(['TransactionId' => 'T1', 'RedirectURL' => 'https://sandboxpg.eps.com.bd/pay/1']),
    'sandboxpgapi.eps.com.bd/v1/EPSEngine/CheckMerchantTransactionStatus*' => Http::response(['Status' => 'Success', 'MerchantTransactionId' => $pay4->reference, 'EPSTransactionId' => 'EPS'.$tag, 'TotalAmount' => '300.00', 'FinancialEntity' => 'Nagad']),
]);
$ge = Gateways::driver('eps');
ok(($ge->start($o4, $pay4)['redirect'] ?? '') === 'https://sandboxpg.eps.com.bd/pay/1', 'EPS: token + initialize → redirect');
ok(Http::recorded(fn ($r) => str_contains($r->url(), 'GetToken') && $r->header('x-hash')[0] === base64_encode(hash_hmac('sha512', 'user@example.com', 'HASHKEY', true)))->isNotEmpty(), 'EPS: x-hash = base64(HMAC-SHA512)');
ok(($ge->verify($pay4)['status'] ?? '') === 'success', 'EPS: verified with CheckMerchantTransactionStatus');

Gateways::save('custom_1', ['enabled' => true, 'title' => 'TestPay', 'mode' => 'live', 'currencies' => 'BDT', 'credentials' => "api_key=K-{$tag}\nsecret=S",
    'init_url' => 'https://pay.example.test/init', 'init_method' => 'post_json', 'init_headers' => 'Authorization: Bearer {cred:api_key}',
    'init_fields' => "amount={amount}\nref={ref}\nok={success_url}\nsig={hmac256:secret|{ref}{amount}}", 'redirect_path' => 'data.url',
    'verify_url' => 'https://pay.example.test/status/{ref}', 'verify_method' => 'get', 'status_path' => 'status', 'success_values' => 'PAID', 'fail_values' => 'FAILED', 'amount_path' => 'amount', 'txn_path' => 'id']);
$o5 = $mk(450);
$pay5 = $svc->newPayment($o5, 'custom_1');
Http::fake([
    'pay.example.test/init' => Http::response(['data' => ['url' => 'https://pay.example.test/checkout/9']]),
    'pay.example.test/status/*' => Http::response(['status' => 'PAID', 'amount' => '450.00', 'id' => 'CUS'.$tag]),
]);
$gc = Gateways::driver('custom_1');
ok(($gc->start($o5, $pay5)['redirect'] ?? '') === 'https://pay.example.test/checkout/9', 'custom gateway: redirect read from data.url');
ok(Http::recorded(fn ($r) => $r->url() === 'https://pay.example.test/init' && $r->header('Authorization')[0] === 'Bearer K-'.$tag && $r['ref'] === $pay5->reference && $r['amount'] === '450.00' && $r['sig'] === hash_hmac('sha256', $pay5->reference.'450.00', 'S') && str_contains($r['ok'], '/payment/custom_1/return/'))->isNotEmpty(), 'custom gateway: headers, credentials, placeholders and HMAC signature filled');
$v5 = $gc->verify($pay5);
ok($v5['status'] === 'success' && $v5['txn_id'] === 'CUS'.$tag && (float) $v5['amount'] === 450.0, 'custom gateway: status/amount/txn read from the verify response');
ok(! str_contains(json_encode(App\Models\Setting::get('payment_gateways')), 'K-'.$tag), 'gateway secrets are stored encrypted');
ok(http('POST', '/payment/aamarpay/return/NOPE'.$tag.'/success')['code'] === 404, 'unknown payment reference → 404');
foreach (['aamarpay', 'paystation', 'eps', 'custom_1'] as $k) {
    Gateways::save($k, ['enabled' => false]);
}

echo "== Portfolio and blog\n";
$proj = Project::create(['title' => 'E2E Project '.$tag, 'slug' => 'e2e-project-'.$tag, 'summary' => 'A test case study', 'description' => '<p>Body</p>', 'challenge' => 'The challenge', 'status' => 'published', 'is_active' => true, 'sort_order' => 1]);
ok(str_contains(get('/portfolio')['body'], 'E2E Project '.$tag), '/portfolio lists the project');
$pp = get('/portfolio/e2e-project-'.$tag);
ok($pp['code'] === 200 && str_contains(text($pp['body']), 'The challenge'), 'project page shows the case study');
$post = Post::create(['title' => 'E2E Post '.$tag, 'slug' => 'e2e-post-'.$tag, 'excerpt' => 'Ex', 'body' => '<p>Hello world body</p>', 'status' => 'published', 'published_at' => now()->subMinute()]);
ok(str_contains(get('/blog')['body'], 'E2E Post '.$tag), '/blog lists the post');
ok(str_contains(text(get('/blog/e2e-post-'.$tag)['body']), 'Hello world body'), 'post page renders');
$menu = get('/about')['body'];
ok(str_contains($menu, '/portfolio"') && str_contains($menu, '/blog"') && str_contains($menu, '/shop"'), 'menu links to portfolio, blog and shop');

echo "== WordPress importer (faked source site)\n";
$src = 'https://old-'.$tag.'.example.com';
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
Http::fake([
    $src.'/wp-json/wp/v2/posts*' => Http::response([[
        'id' => 11, 'slug' => 'hello-'.$tag, 'link' => $src.'/hello/', 'date_gmt' => '2025-03-01T10:00:00',
        'title' => ['rendered' => 'Old &amp; Gold '.$tag], 'excerpt' => ['rendered' => '<p>Short excerpt</p>'],
        'content' => ['rendered' => '<p>Body text</p><img src="'.$src.'/wp-content/uploads/2025/03/pic-'.$tag.'.png" srcset="x 2x"><img src="https://cdn.other.com/a.png">'],
        '_embedded' => ['wp:featuredmedia' => [['source_url' => $src.'/wp-content/uploads/2025/03/cover-'.$tag.'.png']], 'wp:term' => [[['taxonomy' => 'category', 'slug' => 'news-'.$tag, 'name' => 'News']], [['taxonomy' => 'post_tag', 'name' => 'web']]]],
    ]], 200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1']),
    $src.'/wp-json/wp/v2/types' => Http::response(['post' => ['name' => 'Posts', 'rest_base' => 'posts'], 'portfolio' => ['name' => 'Portfolio', 'rest_base' => 'portfolio', 'rest_namespace' => 'wp/v2']]),
    $src.'/wp-json/wp/v2/portfolio*' => Http::response([[
        'id' => 21, 'slug' => 'shop-site-'.$tag, 'link' => $src.'/portfolio/shop-site/', 'title' => ['rendered' => 'Shop Site '.$tag], 'excerpt' => ['rendered' => ''],
        'content' => ['rendered' => '<p>We built it.</p><img src="'.$src.'/wp-content/uploads/2024/01/shot-'.$tag.'.png" alt="Home">'],
        '_embedded' => ['wp:term' => [[['name' => 'Web Design']]]],
    ]], 200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1']),
    $src.'/wp-json/wc/store/v1/products*' => Http::response([[
        'id' => 31, 'slug' => 'theme-'.$tag, 'name' => 'Theme '.$tag, 'permalink' => $src.'/product/theme/', 'sku' => '',
        'prices' => ['regular_price' => '250000', 'sale_price' => '199900', 'currency_code' => 'BDT', 'currency_minor_unit' => 2],
        'description' => '<p>Desc</p>', 'short_description' => '', 'images' => [['src' => $src.'/wp-content/uploads/2024/02/p-'.$tag.'.png']], 'categories' => [['slug' => 'themes-'.$tag, 'name' => 'Themes']], 'tags' => [],
    ]], 200, ['X-WP-Total' => '1', 'X-WP-TotalPages' => '1']),
    $src.'/wp-content/uploads/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
]);
$imp = new WordPressImporter($src);
$found = $imp->discover();
ok($found['posts'] === 1 && count($found['portfolio']) === 1 && $found['products'] === 1, 'discover finds posts, portfolio type and products');
$res = $imp->all(true, 60);
$ip = Post::where('slug', 'hello-'.$tag)->first();
ok($ip && $ip->status === 'published' && $ip->title === 'Old & Gold '.$tag && $ip->published_at->format('Y-m-d') === '2025-03-01', 'post imported with title + original date');
ok($ip && str_contains($ip->body, '/uploads/2025/03/pic-'.$tag.'.png') && ! str_contains($ip->body, 'srcset') && str_contains($ip->body, 'cdn.other.com') && is_file(public_path('uploads/2025/03/pic-'.$tag.'.png')), 'post images downloaded and relinked (external left alone)');
ok($ip && $ip->image === 'uploads/2025/03/cover-'.$tag.'.png' && $ip->category?->name === 'News', 'featured image + category');
ok(App\Models\Media::where('path', 'uploads/2025/03/cover-'.$tag.'.png')->exists() && App\Models\Media::where('path', 'uploads/2025/03/pic-'.$tag.'.png')->exists(), 'imported images appear in the media library');
$iproj = Project::where('slug', 'shop-site-'.$tag)->first();
ok($iproj && $iproj->image === 'uploads/2024/01/shot-'.$tag.'.png' && $iproj->category === 'Web Design', 'portfolio item imported');
$iprod = Product::where('slug', 'theme-'.$tag)->first();
ok($iprod && $iprod->status === 'draft' && (float) $iprod->price === 2500.0 && (float) $iprod->sale_price === 1999.0, 'product imported as DRAFT with correct prices');
$again = $imp->all(true, 60);
ok(($again['results']['posts']['imported'] ?? 1) === 0 && Post::where('slug', 'hello-'.$tag)->count() === 1, 're-running the import creates no duplicates');
ok(ImportLog::where('source', $src)->count() === 3, 'inventory report has one row per source item');

echo "== Cleanup\n";
foreach (['uploads/2025/03/pic-'.$tag.'.png', 'uploads/2025/03/cover-'.$tag.'.png', 'uploads/2024/01/shot-'.$tag.'.png', 'uploads/2024/02/p-'.$tag.'.png'] as $fpath) {
    @unlink(public_path($fpath));
    App\Models\Media::where('path', $fpath)->delete();
}
ok(true, 'done');

if ($failures) {
    echo "\n{$failures} check(s) failed\n";
    exit(1);
}
echo "\nAll store checks passed\n";
