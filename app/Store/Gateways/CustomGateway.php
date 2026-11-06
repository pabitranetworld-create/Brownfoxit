<?php

namespace App\Store\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * "Custom gateway": connect most hosted-checkout gateways WITHOUT code.
 *
 * Start: we call the gateway's "create payment" URL (JSON, form or GET) with your fields, read the
 * payment page URL from the answer and send the customer there — or post a form straight to the gateway.
 * Verify: after the customer returns we call the gateway's "transaction status" URL and check one field.
 *
 * Placeholders usable in URLs, headers and fields:
 *   {ref} {amount} {amount_int} {currency} {order_number} {name} {email} {phone} {address} {city}
 *   {success_url} {fail_url} {cancel_url} {ipn_url} {items} {ip} {cred:KEY} (a value from "Credentials")
 *   {md5:TEXT} {sha256:TEXT} {hmac256:KEY|TEXT} {hmac512b64:KEY|TEXT} – simple signatures (KEY = credential name)
 * Gateways that need a separate login/token step use a small driver class instead (see PayStation, EPS).
 */
class CustomGateway extends Gateway
{
    public static function name(): string
    {
        return 'Custom gateway';
    }

    public static function defaults(string $key): array
    {
        return array_merge(parent::defaults($key), [
            'title' => $key === 'custom_2' ? 'Custom gateway 2' : 'Custom gateway', 'sort' => 30,
            'currencies' => 'BDT', 'init_method' => 'post_json', 'init_headers' => '', 'init_fields' => "store_id={cred:store_id}\namount={amount}\ncurrency={currency}\ntran_id={ref}\nsuccess_url={success_url}\nfail_url={fail_url}\ncancel_url={cancel_url}\ncus_name={name}\ncus_email={email}\ncus_phone={phone}",
            'redirect_path' => 'payment_url', 'verify_method' => 'get', 'verify_headers' => '', 'verify_fields' => "tran_id={ref}\nstore_id={cred:store_id}",
            'status_path' => 'status', 'success_values' => 'success,successful,paid,completed', 'fail_values' => 'failed,cancelled,canceled', 'amount_path' => 'amount', 'txn_path' => 'transaction_id', 'method_path' => '',
            'callback_ref_param' => '',
        ]);
    }

    public static function fields(string $key): array
    {
        return array_merge(self::commonFields(), [
            ['key' => 'mode', 'label' => 'মোড (শুধু তথ্যের জন্য — URL-গুলো নিচে নিজে দিন)', 'type' => 'select', 'options' => ['sandbox' => 'Test', 'live' => 'Live']],
            ['key' => 'currencies', 'label' => 'কোন কারেন্সি নেয় (কমা দিয়ে)', 'type' => 'text', 'help' => 'যেমন BDT অথবা BDT,USD'],
            ['key' => 'credentials', 'label' => 'Credentials (প্রতি লাইনে key=value, এনক্রিপ্ট করে রাখা হয়)', 'type' => 'secret_text', 'help' => "যেমন:\nstore_id=abc123\napi_key=xxxx\nখালি রাখলে আগেরগুলো থাকবে।"],
            ['key' => 'h_init', 'label' => '১. পেমেন্ট শুরু (Create payment)', 'type' => 'heading'],
            ['key' => 'init_url', 'label' => 'Create payment URL', 'type' => 'text', 'help' => 'যেমন https://sandbox.gateway.com/api/payment/create'],
            ['key' => 'init_method', 'label' => 'কীভাবে পাঠাবে', 'type' => 'select', 'options' => ['post_json' => 'POST (JSON) — উত্তরে পেমেন্ট লিংক আসে', 'post_form' => 'POST (form) — উত্তরে পেমেন্ট লিংক আসে', 'get' => 'GET — উত্তরে পেমেন্ট লিংক আসে', 'browser_form' => 'গ্রাহকের ব্রাউজার সরাসরি গেটওয়েতে ফর্ম POST করবে', 'browser_get' => 'গ্রাহককে সরাসরি URL-এ পাঠাবে (fields query string-এ)']],
            ['key' => 'init_headers', 'label' => 'Headers (প্রতি লাইনে Name: value)', 'type' => 'textarea', 'help' => 'যেমন Authorization: Bearer {cred:api_key}'],
            ['key' => 'init_fields', 'label' => 'Fields (প্রতি লাইনে name=value)', 'type' => 'textarea', 'help' => 'Placeholders: {ref} {amount} {amount_int} {currency} {order_number} {name} {email} {phone} {address} {city} {success_url} {fail_url} {cancel_url} {ipn_url} {items} {ip} {cred:KEY} {md5:…} {sha256:…} {hmac256:KEY|…} {hmac512b64:KEY|…}'],
            ['key' => 'redirect_path', 'label' => 'উত্তরের কোন ঘরে পেমেন্ট লিংক থাকে (dot path)', 'type' => 'text', 'help' => 'যেমন payment_url অথবা data.checkout_url'],
            ['key' => 'h_verify', 'label' => '২. পেমেন্ট যাচাই (Transaction status)', 'type' => 'heading'],
            ['key' => 'verify_url', 'label' => 'Transaction status URL', 'type' => 'text'],
            ['key' => 'verify_method', 'label' => 'কীভাবে পাঠাবে', 'type' => 'select', 'options' => ['get' => 'GET', 'post_json' => 'POST (JSON)', 'post_form' => 'POST (form)']],
            ['key' => 'verify_headers', 'label' => 'Headers', 'type' => 'textarea'],
            ['key' => 'verify_fields', 'label' => 'Fields', 'type' => 'textarea'],
            ['key' => 'status_path', 'label' => 'উত্তরের কোন ঘরে স্ট্যাটাস থাকে', 'type' => 'text', 'help' => 'যেমন pay_status অথবা data.status'],
            ['key' => 'success_values', 'label' => 'সফল হলে স্ট্যাটাসের মান (কমা দিয়ে)', 'type' => 'text'],
            ['key' => 'fail_values', 'label' => 'ব্যর্থ হলে স্ট্যাটাসের মান (কমা দিয়ে)', 'type' => 'text'],
            ['key' => 'amount_path', 'label' => 'টাকার পরিমাণের ঘর (মিলিয়ে দেখা হবে)', 'type' => 'text'],
            ['key' => 'txn_path', 'label' => 'গেটওয়ের transaction ID-র ঘর', 'type' => 'text'],
            ['key' => 'method_path', 'label' => 'পেমেন্ট মাধ্যমের ঘর (ঐচ্ছিক)', 'type' => 'text'],
        ]);
    }

    public static function help(): string
    {
        return 'যেসব গেটওয়ে "পেমেন্ট তৈরি → পেমেন্ট পেজে পাঠানো → transaction status দিয়ে যাচাই" নিয়মে চলে (বেশিরভাগই তাই), সেগুলো কোড ছাড়া এখানে যোগ করা যায়। গেটওয়ের ডকুমেন্টেশন থেকে URL আর ফিল্ডের নাম বসান, তারপর Test mode-এ একটা অর্ডার দিয়ে দেখুন। যে গেটওয়েতে আলাদা login/token ধাপ লাগে, সেটার জন্য ছোট একটা driver ফাইল লাগবে।';
    }

    public function configured(): bool
    {
        return filled($this->config['init_url'] ?? null) && filled($this->config['verify_url'] ?? null) && filled($this->config['status_path'] ?? null);
    }

    public function supports(string $currency): bool
    {
        $list = array_filter(array_map(fn ($c) => strtoupper(trim($c)), explode(',', (string) ($this->config['currencies'] ?? ''))));

        return ! $list || in_array(strtoupper($currency), $list, true);
    }

    protected function creds(): array
    {
        $out = [];
        foreach (preg_split('/\R/', (string) ($this->config['credentials'] ?? '')) as $line) {
            if (str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $out[trim($k)] = trim($v);
            }
        }

        return $out;
    }

    /** Replace placeholders in a template. */
    public function fill(string $tpl, array $vars): string
    {
        $creds = $this->creds();
        $tpl = preg_replace_callback('/\{cred:([A-Za-z0-9_.-]+)\}/', fn ($m) => $creds[$m[1]] ?? '', $tpl);
        $tpl = strtr($tpl, $vars);
        // signatures, innermost first
        for ($i = 0; $i < 3 && preg_match('/\{(md5|sha256|hmac256|hmac512b64):([^{}]*)\}/', $tpl); $i++) {
            $tpl = preg_replace_callback('/\{(md5|sha256|hmac256|hmac512b64):([^{}]*)\}/', function ($m) use ($creds) {
                [$fn, $arg] = [$m[1], $m[2]];
                if (in_array($fn, ['hmac256', 'hmac512b64'], true)) {
                    [$k, $text] = array_pad(explode('|', $arg, 2), 2, '');
                    $key = $creds[$k] ?? $k;

                    return $fn === 'hmac256' ? hash_hmac('sha256', $text, $key) : base64_encode(hash_hmac('sha512', $text, $key, true));
                }

                return $fn === 'md5' ? md5($arg) : hash('sha256', $arg);
            }, $tpl);
        }

        return $tpl;
    }

    protected function vars(Payment $payment): array
    {
        $order = $payment->order;
        $c = self::customer($order);
        $u = $this->urls($payment);

        return [
            '{ref}' => $payment->reference, '{amount}' => number_format((float) $payment->amount, 2, '.', ''), '{amount_int}' => (string) (int) ceil((float) $payment->amount),
            '{currency}' => strtoupper($payment->currency), '{order_number}' => $order->number, '{name}' => $c['name'], '{email}' => $c['email'],
            '{phone}' => $c['phone'], '{address}' => $c['address'], '{city}' => $c['city'], '{success_url}' => $u['success'], '{fail_url}' => $u['fail'],
            '{cancel_url}' => $u['cancel'], '{ipn_url}' => $u['ipn'], '{items}' => mb_substr(self::itemsText($order), 0, 200), '{ip}' => (string) request()->ip(),
        ];
    }

    protected function pairs(string $text, array $vars, string $sep = '='): array
    {
        $out = [];
        foreach (preg_split('/\R/', $text) as $line) {
            if (trim($line) === '' || ! str_contains($line, $sep)) {
                continue;
            }
            [$k, $v] = explode($sep, $line, 2);
            $out[trim($k)] = $this->fill(trim($v), $vars);
        }

        return $out;
    }

    protected function call(string $method, string $url, array $headers, array $fields): array
    {
        $http = Http::acceptJson()->withHeaders($headers)->timeout(30);
        $res = match ($method) {
            'post_json' => $http->post($url, $fields),
            'post_form' => $http->asForm()->post($url, $fields),
            default => $http->get($url, $fields),
        };
        $json = $res->json();

        return [is_array($json) ? $json : [], $res];
    }

    public function start(Order $order, Payment $payment): array
    {
        $payment->setRelation('order', $order);
        $vars = $this->vars($payment);
        $url = $this->fill((string) $this->config['init_url'], $vars);
        $fields = $this->pairs((string) $this->config['init_fields'], $vars);
        $method = (string) ($this->config['init_method'] ?? 'post_json');
        if ($method === 'browser_form') {
            return ['form' => ['action' => $url, 'fields' => $fields]];
        }
        if ($method === 'browser_get') {
            return ['redirect' => $url.(str_contains($url, '?') ? '&' : '?').http_build_query($fields)];
        }
        try {
            [$j, $res] = $this->call($method, $url, $this->pairs((string) $this->config['init_headers'], $vars, ':'), $fields);
            $link = Arr::get($j, (string) $this->config['redirect_path']);
            $payment->remember('initiate', ['http' => $res->status()]);
            if (is_string($link) && preg_match('#^https?://#', $link)) {
                return ['redirect' => $link];
            }

            return ['error' => $this->title().': the payment could not be created. '.mb_substr($res->body(), 0, 200)];
        } catch (Throwable $e) {
            return ['error' => $this->title().' is not reachable right now.'];
        }
    }

    public function verify(Payment $payment, ?Request $request = null): array
    {
        $vars = $this->vars($payment);
        try {
            [$j] = $this->call((string) ($this->config['verify_method'] ?? 'get'), $this->fill((string) $this->config['verify_url'], $vars), $this->pairs((string) $this->config['verify_headers'], $vars, ':'), $this->pairs((string) $this->config['verify_fields'], $vars));
            $status = strtolower((string) Arr::get($j, (string) $this->config['status_path'], ''));
            $in = fn ($list) => in_array($status, array_filter(array_map(fn ($v) => strtolower(trim($v)), explode(',', (string) $list))), true);
            $amount = Arr::get($j, (string) ($this->config['amount_path'] ?? ''));
            $payment->remember('verify', ['status' => $status, 'amount' => $amount]);

            return [
                'status' => $in($this->config['success_values'] ?? '') ? 'success' : ($in($this->config['fail_values'] ?? '') ? 'failed' : 'pending'),
                'txn_id' => ($t = Arr::get($j, (string) ($this->config['txn_path'] ?? ''))) ? (string) $t : null,
                'amount' => is_numeric($amount) ? (float) $amount : null,
                'method' => filled($this->config['method_path'] ?? '') ? (string) Arr::get($j, $this->config['method_path']) : null,
                'message' => $status,
            ];
        } catch (Throwable $e) {
            return ['status' => 'pending', 'message' => 'Could not reach the gateway to verify'];
        }
    }
}
