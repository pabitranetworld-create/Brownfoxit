<?php

namespace App\Store\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * aamarPay (Bangladesh) — JSON API.
 * 1. POST {base}/jsonpost.php (store_id, signature_key, tran_id, amount, currency, cus_*, success/fail/cancel_url, type=json) → payment_url
 * 2. aamarPay POSTs the result to success_url / fail_url (pay_status, mer_txnid, pg_txnid …)
 * 3. We confirm with GET {base}/api/v1/trxcheck/request.php?request_id=…&store_id=…&signature_key=…&type=json
 * Sandbox: https://sandbox.aamarpay.com (public test store "aamarpaytest"), live: https://secure.aamarpay.com
 */
class AamarPay extends Gateway
{
    public static function name(): string
    {
        return 'aamarPay';
    }

    public static function defaults(string $key): array
    {
        return array_merge(parent::defaults($key), ['sort' => 2, 'description' => 'bKash, Nagad, Rocket, cards and internet banking via aamarPay.']);
    }

    public static function fields(string $key): array
    {
        return array_merge(self::commonFields(), [
            ['key' => 'mode', 'label' => 'মোড', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox (sandbox.aamarpay.com)', 'live' => 'Live (secure.aamarpay.com)']],
            ['key' => 'store_id', 'label' => 'Store ID', 'type' => 'text', 'help' => 'Sandbox-এ টেস্ট করতে: aamarpaytest'],
            ['key' => 'signature_key', 'label' => 'Signature Key', 'type' => 'secret', 'help' => 'Sandbox টেস্ট key: dbb74894e82415a2f7ff0ec3a97e4183'],
        ]);
    }

    public static function help(): string
    {
        return 'aamarPay থেকে Store ID ও Signature Key পাবেন। Sandbox-এ পাবলিক টেস্ট Store ID "aamarpaytest" ব্যবহার করে পুরো পেমেন্ট টেস্ট করা যায়। BDT ও USD সমর্থন করে।';
    }

    public function configured(): bool
    {
        return filled($this->config['store_id'] ?? null) && filled($this->config['signature_key'] ?? null);
    }

    public function supports(string $currency): bool
    {
        return in_array(strtoupper($currency), ['BDT', 'USD'], true);
    }

    protected function base(): string
    {
        return $this->sandbox() ? 'https://sandbox.aamarpay.com' : 'https://secure.aamarpay.com';
    }

    public function start(Order $order, Payment $payment): array
    {
        $c = self::customer($order);
        $u = $this->urls($payment);
        try {
            $res = Http::acceptJson()->timeout(30)->post($this->base().'/jsonpost.php', [
                'store_id' => $this->config['store_id'],
                'signature_key' => $this->config['signature_key'],
                'tran_id' => $payment->reference,
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
                'currency' => strtoupper($payment->currency),
                'desc' => mb_substr('Order '.$order->number.': '.self::itemsText($order), 0, 250),
                'cus_name' => $c['name'],
                'cus_email' => $c['email'],
                'cus_phone' => $c['phone'],
                'cus_add1' => $c['address'],
                'cus_city' => $c['city'],
                'cus_postcode' => $c['postcode'],
                'cus_country' => $c['country'],
                'success_url' => $u['success'],
                'fail_url' => $u['fail'],
                'cancel_url' => $u['cancel'],
                'opt_a' => $order->number,
                'type' => 'json',
            ]);
            $j = (array) $res->json();
            $payment->remember('initiate', ['result' => $j['result'] ?? null]);
            if (in_array((string) ($j['result'] ?? ''), ['true', '1'], true) && ! empty($j['payment_url'])) {
                return ['redirect' => $j['payment_url']];
            }
            $msg = is_array($j) ? json_encode($j) : $res->body();

            return ['error' => 'aamarPay could not create the payment. '.mb_substr((string) $msg, 0, 200)];
        } catch (Throwable $e) {
            return ['error' => 'aamarPay is not reachable right now. Please try again or choose another method.'];
        }
    }

    public function verify(Payment $payment, ?Request $request = null): array
    {
        try {
            $j = (array) Http::acceptJson()->timeout(30)->get($this->base().'/api/v1/trxcheck/request.php', [
                'request_id' => $payment->reference,
                'store_id' => $this->config['store_id'],
                'signature_key' => $this->config['signature_key'],
                'type' => 'json',
            ])->json();
            $payment->remember('verify', array_intersect_key($j, array_flip(['pay_status', 'status_code', 'pg_txnid', 'mer_txnid', 'amount', 'currency', 'payment_type'])));
            if (($j['mer_txnid'] ?? $payment->reference) !== $payment->reference) {
                return ['status' => 'pending', 'message' => 'Transaction reference mismatch'];
            }
            $s = strtolower((string) ($j['pay_status'] ?? ''));
            if (! empty($j['currency']) && strtoupper((string) $j['currency']) !== strtoupper($payment->currency) && strtoupper((string) ($j['currency_merchant'] ?? '')) !== strtoupper($payment->currency)) {
                return ['status' => 'failed', 'message' => 'Currency mismatch'];
            }

            return [
                'status' => match ($s) { 'successful', 'success' => 'success', 'failed' => 'failed', 'cancelled', 'canceled' => 'cancelled', default => 'pending' },
                'txn_id' => $j['pg_txnid'] ?? null,
                'amount' => isset($j['amount']) ? (float) $j['amount'] : null,
                'method' => $j['payment_type'] ?? null,
                'message' => $j['pay_status'] ?? null,
            ];
        } catch (Throwable $e) {
            return ['status' => 'pending', 'message' => 'Could not reach aamarPay to verify'];
        }
    }

    public function test(): array
    {
        if (! $this->configured()) {
            return [false, 'Store ID ও Signature Key দিন।'];
        }
        try {
            $res = Http::acceptJson()->timeout(20)->post($this->base().'/jsonpost.php', [
                'store_id' => $this->config['store_id'], 'signature_key' => $this->config['signature_key'], 'tran_id' => 'TEST'.time(),
                'amount' => '10.00', 'currency' => 'BDT', 'desc' => 'Connection test', 'cus_name' => 'Test', 'cus_email' => 'test@example.com',
                'cus_phone' => '01700000000', 'cus_add1' => 'Dhaka', 'cus_city' => 'Dhaka', 'cus_country' => 'Bangladesh',
                'success_url' => url('/'), 'fail_url' => url('/'), 'cancel_url' => url('/'), 'type' => 'json',
            ]);
            $j = (array) $res->json();
            $ok = in_array((string) ($j['result'] ?? ''), ['true', '1'], true) && ! empty($j['payment_url']);

            return [$ok, $ok ? 'সংযোগ ঠিক আছে — aamarPay একটা টেস্ট পেমেন্ট লিংক দিয়েছে (কোনো টাকা কাটা হয়নি)।' : 'aamarPay রাজি হয়নি: '.mb_substr($res->body(), 0, 200)];
        } catch (Throwable $e) {
            return [false, 'aamarPay-এ পৌঁছানো যায়নি: '.$e->getMessage()];
        }
    }
}
