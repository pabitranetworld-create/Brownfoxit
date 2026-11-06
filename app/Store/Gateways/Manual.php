<?php

namespace App\Store\Gateways;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Manual payment (bank transfer, bKash/Nagad personal or merchant number …).
 * The order is put "on hold"; you check the payment and confirm it in Admin → Orders.
 * Nothing is ever marked as paid automatically.
 */
class Manual extends Gateway
{
    public static function name(): string
    {
        return 'Manual payment';
    }

    public static function defaults(string $key): array
    {
        return $key === 'manual_mobile'
            ? ['enabled' => false, 'mode' => 'live', 'title' => 'bKash / Nagad (manual)', 'description' => 'Send money to our number and enter the transaction ID.', 'instructions' => "Send the order total to [your bKash/Nagad number] using \"Send Money\" or \"Payment\".\nUse your order number as reference.\nThen enter the transaction ID below.", 'ask_reference' => true, 'reference_label' => 'Transaction ID', 'sort' => 50]
            : ['enabled' => false, 'mode' => 'live', 'title' => 'Bank transfer', 'description' => 'Pay by bank transfer. We start after the money arrives.', 'instructions' => "Bank: [bank name]\nAccount name: [name]\nAccount number: [number]\nBranch / routing: [details]\nReference: your order number", 'ask_reference' => false, 'reference_label' => 'Transfer reference', 'sort' => 60];
    }

    public static function fields(string $key): array
    {
        return array_merge(self::commonFields(), [
            ['key' => 'instructions', 'label' => 'পেমেন্টের নির্দেশনা (চেকআউট, অর্ডার পেজ আর ইমেইলে দেখাবে)', 'type' => 'textarea'],
            ['key' => 'ask_reference', 'label' => 'গ্রাহকের কাছে Transaction ID / reference চাইবে', 'type' => 'checkbox'],
            ['key' => 'reference_label', 'label' => 'Reference ঘরের নাম', 'type' => 'text'],
        ]);
    }

    public static function help(): string
    {
        return 'ম্যানুয়াল পেমেন্টে অর্ডার "On hold" থাকে। টাকা পেয়েছেন নিশ্চিত হলে Admin → Orders থেকে "পেমেন্ট নিশ্চিত করুন" চাপুন — তখন ডাউনলোড চালু হয় ও গ্রাহক ইমেইল পান।';
    }

    public function configured(): bool
    {
        return filled($this->config['instructions'] ?? null);
    }

    public function asksReference(): bool
    {
        return (bool) ($this->config['ask_reference'] ?? false);
    }

    public function start(Order $order, Payment $payment): array
    {
        return ['manual' => true];
    }

    public function verify(Payment $payment, ?Request $request = null): array
    {
        return ['status' => 'pending', 'message' => 'Waiting for manual confirmation'];
    }

    public function test(): array
    {
        return [$this->configured(), $this->configured() ? 'নির্দেশনা দেওয়া আছে।' : 'নির্দেশনা লিখুন।'];
    }
}
