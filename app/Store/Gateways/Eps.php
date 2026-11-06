<?php

namespace App\Store\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * EPS — Easy Payment System (Bangladesh), eps.com.bd. Every request carries an "x-hash" header:
 * base64( HMAC-SHA512( value, HashKey ) ).
 * 1. POST {base}/v1/Auth/GetToken {userName, password}, x-hash = hash(userName) → token
 * 2. POST {base}/v1/EPSEngine/InitializeEPS (Bearer token, x-hash = hash(merchantTransactionId)) → RedirectURL
 * 3. GET  {base}/v1/EPSEngine/CheckMerchantTransactionStatus?merchantTransactionId=… (x-hash = hash(merchantTransactionId)) → Status
 * Sandbox: https://sandboxpgapi.eps.com.bd, live: https://pgapi.eps.com.bd
 * (EPS validates the request origin: the merchant domain must be registered with EPS.)
 */
class Eps extends Gateway
{
    public static function name(): string
    {
        return 'EPS';
    }

    public static function defaults(string $key): array
    {
        return array_merge(parent::defaults($key), ['sort' => 3, 'description' => 'Pay with mobile banking, cards or internet banking through EPS.']);
    }

    public static function fields(string $key): array
    {
        return array_merge(self::commonFields(), [
            ['key' => 'mode', 'label' => 'মোড', 'type' => 'select', 'options' => ['sandbox' => 'Sandbox (sandboxpgapi.eps.com.bd)', 'live' => 'Live (pgapi.eps.com.bd)']],
            ['key' => 'merchant_id', 'label' => 'Merchant ID', 'type' => 'text'],
            ['key' => 'store_id', 'label' => 'Store ID', 'type' => 'text'],
            ['key' => 'username', 'label' => 'Username (ইমেইল)', 'type' => 'text'],
            ['key' => 'password', 'label' => 'Password', 'type' => 'secret'],
            ['key' => 'hash_key', 'label' => 'Hash Key', 'type' => 'secret'],
        ]);
    }

    public static function help(): string
    {
        return 'EPS merchant portal থেকে Merchant ID, Store ID, Username, Password ও Hash Key পাবেন (Sandbox ও Live আলাদা)। আপনার ডোমেইন EPS-এ রেজিস্টার করা থাকতে হবে। শুধু BDT।';
    }

    public function configured(): bool
    {
        foreach (['merchant_id', 'store_id', 'username', 'password', 'hash_key'] as $k) {
            if (blank($this->config[$k] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public function supports(string $currency): bool
    {
        return strtoupper($currency) === 'BDT';
    }

    protected function base(): string
    {
        return $this->sandbox() ? 'https://sandboxpgapi.eps.com.bd' : 'https://pgapi.eps.com.bd';
    }

    public function hash(string $value): string
    {
        return base64_encode(hash_hmac('sha512', $value, (string) $this->config['hash_key'], true));
    }

    protected function token(): ?string
    {
        $res = Http::acceptJson()->withHeaders(['x-hash' => $this->hash((string) $this->config['username'])])->timeout(20)
            ->post($this->base().'/v1/Auth/GetToken', ['userName' => $this->config['username'], 'password' => $this->config['password']]);

        return $res->json('token') ?: null;
    }

    public function start(Order $order, Payment $payment): array
    {
        $c = self::customer($order);
        $u = $this->urls($payment);
        try {
            $token = $this->token();
            if (! $token) {
                return ['error' => 'EPS: authentication failed. Please choose another payment method.'];
            }
            $amount = (float) $payment->amount;
            $res = Http::acceptJson()->withToken($token)->withHeaders(['x-hash' => $this->hash($payment->reference)])->timeout(30)
                ->post($this->base().'/v1/EPSEngine/InitializeEPS', [
                    'merchantId' => $this->config['merchant_id'],
                    'storeId' => $this->config['store_id'],
                    'CustomerOrderId' => $order->number,
                    'merchantTransactionId' => $payment->reference,
                    'transactionTypeId' => 1,
                    'totalAmount' => $amount,
                    'successUrl' => $u['success'],
                    'failUrl' => $u['fail'],
                    'cancelUrl' => $u['cancel'],
                    'customerName' => $c['name'],
                    'customerEmail' => $c['email'],
                    'CustomerAddress' => $c['address'],
                    'CustomerAddress2' => '',
                    'CustomerCity' => $c['city'],
                    'CustomerState' => $c['city'],
                    'CustomerPostcode' => $c['postcode'],
                    'CustomerCountry' => 'BD',
                    'CustomerPhone' => $c['phone'],
                    'productName' => mb_substr(self::itemsText($order), 0, 100) ?: 'Order '.$order->number,
                    'productProfile' => 'general',
                    'productCategory' => 'general',
                    'ipAddress' => (string) request()->ip(),
                    'version' => '1',
                    'financialEntityId' => 0,
                    'transitionStatusId' => 0,
                    'ShippingMethod' => $order->needs_shipping ? 'Courier' : 'NO',
                    'NoOfItem' => (string) $order->items->sum('qty'),
                    'ProductList' => $order->items->map(fn ($i) => ['ProductName' => mb_substr($i->name, 0, 100), 'NoOfItem' => (string) $i->qty, 'ProductProfile' => 'general', 'ProductCategory' => 'general', 'ProductPrice' => (string) $i->price])->values()->all(),
                ]);
            $j = (array) $res->json();
            $payment->remember('initiate', ['TransactionId' => $j['TransactionId'] ?? null, 'ErrorCode' => $j['ErrorCode'] ?? null, 'ErrorMessage' => $j['ErrorMessage'] ?? null]);
            if ($res->successful() && empty($j['ErrorMessage']) && ! empty($j['RedirectURL'])) {
                return ['redirect' => $j['RedirectURL']];
            }

            return ['error' => 'EPS: '.($j['ErrorMessage'] ?? 'could not create the payment (HTTP '.$res->status().')')];
        } catch (Throwable $e) {
            return ['error' => 'EPS is not reachable right now. Please try again or choose another method.'];
        }
    }

    public function verify(Payment $payment, ?Request $request = null): array
    {
        try {
            $token = $this->token();
            if (! $token) {
                return ['status' => 'pending', 'message' => 'EPS authentication failed'];
            }
            $j = (array) Http::acceptJson()->withToken($token)->withHeaders(['x-hash' => $this->hash($payment->reference)])->timeout(30)
                ->get($this->base().'/v1/EPSEngine/CheckMerchantTransactionStatus', ['merchantTransactionId' => $payment->reference])->json();
            $payment->remember('verify', array_intersect_key($j, array_flip(['Status', 'MerchantTransactionId', 'EPSTransactionId', 'TotalAmount', 'FinancialEntity', 'ErrorMessage'])));
            if (($j['MerchantTransactionId'] ?? $payment->reference) !== $payment->reference) {
                return ['status' => 'pending', 'message' => 'Transaction reference mismatch'];
            }
            $s = strtolower((string) ($j['Status'] ?? ''));

            return [
                'status' => match ($s) { 'success', 'successful' => 'success', 'failed', 'fail' => 'failed', 'cancel', 'cancelled', 'canceled' => 'cancelled', default => 'pending' },
                'txn_id' => $j['EPSTransactionId'] ?? $j['TransactionId'] ?? null,
                'amount' => isset($j['TotalAmount']) ? (float) $j['TotalAmount'] : null,
                'method' => $j['FinancialEntity'] ?? null,
                'message' => $j['ErrorMessage'] ?? null,
            ];
        } catch (Throwable $e) {
            return ['status' => 'pending', 'message' => 'Could not reach EPS to verify'];
        }
    }

    public function test(): array
    {
        if (! $this->configured()) {
            return [false, 'সব ঘর পূরণ করুন।'];
        }
        try {
            return $this->token() ? [true, 'সংযোগ ঠিক আছে — EPS থেকে token পাওয়া গেছে।'] : [false, 'EPS token দেয়নি। Username, Password ও Hash Key দেখুন।'];
        } catch (Throwable $e) {
            return [false, 'EPS-এ পৌঁছানো যায়নি: '.$e->getMessage()];
        }
    }
}
