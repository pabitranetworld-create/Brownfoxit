<?php

namespace App\Store\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * PayStation (Bangladesh) — https://www.paystation.com.bd/documentation
 * 1. POST {base}/initiate-payment (merchantId, password, invoice_number, payment_amount, cust_*, callback_url) → payment_url
 * 2. Customer pays, PayStation redirects to callback_url?status=…&invoice_number=…&trx_id=…
 * 3. We confirm with POST {base}/transaction-status (header merchantId, body invoice_number) → data.trx_status
 * PayStation provides separate test credentials; the API host is the same.
 */
class PayStation extends Gateway
{
    public static function name(): string
    {
        return 'PayStation';
    }

    public static function defaults(string $key): array
    {
        return array_merge(parent::defaults($key), ['base_url' => 'https://api.paystation.com.bd', 'pay_with_charge' => false, 'sort' => 1, 'description' => 'bKash, Nagad, Rocket, Upay, Visa, Mastercard and more.']);
    }

    public static function fields(string $key): array
    {
        return array_merge(self::commonFields(), [
            ['key' => 'mode', 'label' => 'মোড', 'type' => 'select', 'options' => ['sandbox' => 'Test (PayStation-এর টেস্ট Merchant ID দিয়ে)', 'live' => 'Live (আসল পেমেন্ট)']],
            ['key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text', 'help' => 'যেমন 104-1653730183'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'secret'],
            ['key' => 'pay_with_charge', 'label' => 'গেটওয়ের চার্জ গ্রাহক দেবে (pay_with_charge = 1)', 'type' => 'checkbox'],
            ['key' => 'base_url', 'label' => 'API URL', 'type' => 'text', 'help' => 'সাধারণত বদলানোর দরকার নেই'],
        ]);
    }

    public static function help(): string
    {
        return 'PayStation থেকে Merchant ID ও Password পাবেন (টেস্ট ও লাইভের জন্য আলাদা)। ডকুমেন্টেশন: paystation.com.bd/documentation। শুধু BDT সমর্থন করে।';
    }

    public function configured(): bool
    {
        return filled($this->config['merchant_id'] ?? null) && filled($this->config['password'] ?? null);
    }

    public function supports(string $currency): bool
    {
        return strtoupper($currency) === 'BDT';
    }

    protected function base(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? '') ?: 'https://api.paystation.com.bd', '/');
    }

    public function start(Order $order, Payment $payment): array
    {
        $c = self::customer($order);
        try {
            $res = Http::asForm()->timeout(30)->post($this->base().'/initiate-payment', [
                'merchantId' => $this->config['merchant_id'],
                'password' => $this->config['password'],
                'invoice_number' => $payment->reference,
                'currency' => 'BDT',
                'payment_amount' => (int) ceil((float) $payment->amount),
                'pay_with_charge' => ! empty($this->config['pay_with_charge']) ? 1 : 0,
                'reference' => $order->number,
                'cust_name' => $c['name'],
                'cust_phone' => $c['phone'],
                'cust_email' => $c['email'],
                'cust_address' => $c['address'],
                'callback_url' => $this->urls($payment)['success'],
                'checkout_items' => mb_substr(self::itemsText($order), 0, 250),
            ]);
            $j = (array) $res->json();
            $payment->remember('initiate', array_intersect_key($j, array_flip(['status_code', 'status', 'message', 'invoice_number'])));
            if ((string) ($j['status_code'] ?? '') === '200' && ! empty($j['payment_url'])) {
                return ['redirect' => $j['payment_url']];
            }

            return ['error' => 'PayStation: '.($j['message'] ?? 'could not create the payment (HTTP '.$res->status().')')];
        } catch (Throwable $e) {
            return ['error' => 'PayStation is not reachable right now. Please try again or choose another method.'];
        }
    }

    public function verify(Payment $payment, ?Request $request = null): array
    {
        try {
            $res = Http::asForm()->withHeaders(['merchantId' => (string) $this->config['merchant_id']])->timeout(30)
                ->post($this->base().'/transaction-status', ['invoice_number' => $payment->reference]);
            $j = (array) $res->json();
            $d = (array) ($j['data'] ?? []);
            $payment->remember('verify', array_intersect_key($d, array_flip(['trx_status', 'trx_id', 'payment_amount', 'payment_method', 'invoice_number'])) + ['status_code' => $j['status_code'] ?? null]);
            if ((string) ($j['status_code'] ?? '') !== '200' || ($d['invoice_number'] ?? $payment->reference) !== $payment->reference) {
                return ['status' => $request && strtolower((string) $request->input('status')) === 'canceled' ? 'cancelled' : 'pending', 'message' => $j['message'] ?? 'Transaction not confirmed yet'];
            }
            $s = strtolower((string) ($d['trx_status'] ?? ''));

            return [
                'status' => match (true) { $s === 'success' || $s === 'successful' => 'success', $s === 'failed' => 'failed', $s === 'refund' => 'failed', default => 'pending' },
                'txn_id' => $d['trx_id'] ?? null,
                'amount' => isset($d['payment_amount']) ? (float) $d['payment_amount'] : null,
                'method' => $d['payment_method'] ?? null,
                'message' => $j['message'] ?? null,
            ];
        } catch (Throwable $e) {
            return ['status' => 'pending', 'message' => 'Could not reach PayStation to verify'];
        }
    }

    public function test(): array
    {
        if (! $this->configured()) {
            return [false, 'Merchant ID ও Password দিন।'];
        }
        try {
            $j = (array) Http::asForm()->withHeaders(['merchantId' => (string) $this->config['merchant_id']])->timeout(20)
                ->post($this->base().'/transaction-status', ['invoice_number' => 'TEST-'.time()])->json();

            return [true, 'PayStation সার্ভারে পৌঁছানো গেছে। উত্তর: '.($j['status_code'] ?? '?').' — '.($j['message'] ?? '').' (টেস্ট ইনভয়েস না থাকায় "not found" স্বাভাবিক)'];
        } catch (Throwable $e) {
            return [false, 'PayStation-এ পৌঁছানো যায়নি: '.$e->getMessage()];
        }
    }
}
