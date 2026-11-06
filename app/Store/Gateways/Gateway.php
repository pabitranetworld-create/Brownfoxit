<?php

namespace App\Store\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Base class of every payment gateway driver.
 *
 * start()  → where to send the customer: ['redirect' => url] | ['form' => ['action' => url, 'fields' => []]] | ['manual' => true] | ['error' => message]
 * verify() → asks the GATEWAY (server to server) what happened to a payment:
 *            ['status' => success|failed|cancelled|pending, 'txn_id' => …, 'amount' => float|null, 'method' => …, 'message' => …]
 *            Never trust what the browser sends back — always confirm with the gateway here.
 */
abstract class Gateway
{
    public function __construct(public string $key, public array $config) {}

    abstract public static function name(): string;

    /** Admin form fields: key, label, type (text|textarea|secret|select|checkbox|number), options, help. */
    public static function fields(string $key): array
    {
        return self::commonFields();
    }

    public static function defaults(string $key): array
    {
        return ['enabled' => false, 'mode' => 'sandbox', 'title' => static::name(), 'description' => '', 'sort' => 10];
    }

    /** Short help shown at the top of the admin form (docs link, what to ask the provider). */
    public static function help(): string
    {
        return '';
    }

    public static function commonFields(): array
    {
        return [
            ['key' => 'enabled', 'label' => 'চালু (চেকআউটে দেখাবে)', 'type' => 'checkbox'],
            ['key' => 'title', 'label' => 'চেকআউটে নাম', 'type' => 'text'],
            ['key' => 'description', 'label' => 'চেকআউটে ছোট বিবরণ', 'type' => 'textarea', 'help' => 'যেমন: bKash, Nagad, Rocket, Visa, Mastercard দিয়ে পেমেন্ট করুন'],
            ['key' => 'sort', 'label' => 'ক্রম (ছোট সংখ্যা আগে)', 'type' => 'number'],
        ];
    }

    public function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    public function sandbox(): bool
    {
        return ($this->config['mode'] ?? 'sandbox') !== 'live';
    }

    public function title(): string
    {
        return (string) (($this->config['title'] ?? '') ?: static::name());
    }

    public function description(): string
    {
        return (string) ($this->config['description'] ?? '');
    }

    /** Are the required credentials filled in? */
    abstract public function configured(): bool;

    public function supports(string $currency): bool
    {
        return true;
    }

    /** Manual gateways ask the customer for a reference (e.g. bKash transaction ID) at checkout. */
    public function asksReference(): bool
    {
        return false;
    }

    abstract public function start(Order $order, Payment $payment): array;

    abstract public function verify(Payment $payment, ?Request $request = null): array;

    /** Admin "test connection" button. Returns [ok, message]. */
    public function test(): array
    {
        return [false, 'এই গেটওয়ের জন্য স্বয়ংক্রিয় টেস্ট নেই।'];
    }

    protected function urls(Payment $payment): array
    {
        $base = fn (string $r) => route('payment.return', ['gateway' => $this->key, 'ref' => $payment->reference, 'result' => $r]);

        return ['success' => $base('success'), 'fail' => $base('fail'), 'cancel' => $base('cancel'), 'ipn' => route('payment.ipn', ['gateway' => $this->key])];
    }

    protected static function customer(Order $order): array
    {
        $b = $order->billing ?? [];

        return [
            'name' => trim((string) ($b['name'] ?? 'Customer')) ?: 'Customer',
            'email' => (string) ($b['email'] ?? ''),
            'phone' => preg_replace('/[^0-9+]/', '', (string) ($b['phone'] ?? '')) ?: '01700000000',
            'address' => trim(implode(', ', array_filter([$b['address'] ?? '', $b['city'] ?? '']))) ?: 'N/A',
            'city' => (string) (($b['city'] ?? '') ?: 'Dhaka'),
            'postcode' => (string) (($b['postcode'] ?? '') ?: '1000'),
            'country' => (string) (($b['country'] ?? '') ?: 'Bangladesh'),
        ];
    }

    protected static function itemsText(Order $order): string
    {
        return $order->items->map(fn ($i) => $i->name.' x'.$i->qty)->implode(', ');
    }
}
