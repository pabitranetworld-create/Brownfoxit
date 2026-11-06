<?php

namespace App\Store;

use App\Models\Coupon;
use App\Models\DownloadPermission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Support\SiteMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orders: creation from the cart, the ONLY code path that marks an order as paid,
 * download permissions, stock, status changes and customer emails.
 */
class OrderService
{
    public function __construct(protected CartService $cart) {}

    /** Create an order from the current cart. Totals are recalculated on the server. */
    public function createFromCart(array $billing, ?array $shipping, ?string $note, string $gateway): Order
    {
        return DB::transaction(function () use ($billing, $shipping, $note, $gateway) {
            $t = $this->cart->totals();
            if (! $t['lines']) {
                throw new RuntimeException('Your cart is empty.');
            }
            foreach ($t['lines'] as $l) {
                $p = Product::lockForUpdate()->find($l['product']->id);
                if (! $p || ! $p->inStock($l['variant'], $l['qty'])) {
                    throw new RuntimeException('“'.$l['product']->name.'” is no longer available in that quantity.');
                }
            }
            $user = auth()->user();
            $order = Order::create([
                'number' => $this->nextNumber(),
                'access_key' => Str::random(40),
                'user_id' => $user && ! $user->isAdmin() ? $user->id : null,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $gateway,
                'currency' => $t['currency'],
                'subtotal' => $t['subtotal'],
                'discount' => $t['discount'],
                'shipping' => $t['shipping'],
                'tax' => $t['tax'],
                'total' => $t['total'],
                'coupon_code' => $t['coupon']?->code,
                'billing' => $billing,
                'shipping_address' => $t['needs_shipping'] ? $shipping : null,
                'needs_shipping' => $t['needs_shipping'],
                'customer_note' => $note,
                'ip' => request()->ip(),
            ]);
            foreach ($t['lines'] as $l) {
                $order->items()->create([
                    'product_id' => $l['product']->id, 'variant_key' => $l['variant'], 'name' => $l['product']->name,
                    'variant_name' => $l['variant_name'], 'sku' => $l['product']->variant($l['variant'])['sku'] ?? $l['product']->sku,
                    'type' => $l['product']->type, 'price' => $l['price'], 'qty' => $l['qty'], 'total' => $l['total'],
                ]);
            }
            $order->log('created', 'Order placed. Total '.money($order->total, $order->currency).'.');
            $this->cart->clear();

            return $order;
        });
    }

    protected function nextNumber(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', (string) store('order_prefix', 'BFI'))) ?: 'BFI';
        do {
            $n = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Order::where('number', $n)->exists());

        return $n;
    }

    public function newPayment(Order $order, string $gateway): Payment
    {
        return $order->payments()->create([
            'gateway' => $gateway,
            'reference' => strtoupper(Str::random(4)).$order->id.'T'.now()->format('His').strtoupper(Str::random(3)),
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => 'initiated',
        ]);
    }

    /**
     * Mark a payment as successful AFTER the gateway confirmed it server-to-server.
     * Safe to call many times (callback + IPN): only the first call changes anything.
     */
    public function markPaid(Payment $payment, ?string $gatewayTxnId, ?float $verifiedAmount, ?string $method = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($payment, $gatewayTxnId, $verifiedAmount, $method, $note) {
            $payment = Payment::lockForUpdate()->find($payment->id);
            $order = Order::lockForUpdate()->find($payment->order_id);

            if ($payment->status === 'success' || $order->isPaid()) {
                return $order; // already done (duplicate callback / IPN)
            }
            if ($verifiedAmount !== null && $verifiedAmount + 0.01 < (float) $payment->amount) {
                $payment->update(['status' => 'failed', 'message' => 'Amount mismatch: gateway reported '.$verifiedAmount]);
                $order->update(['status' => 'on_hold', 'payment_status' => 'pending']);
                $order->log('payment', 'Payment amount did not match ('.$verifiedAmount.' instead of '.$payment->amount.'). Order put on hold for review.');

                return $order;
            }
            if ($gatewayTxnId && Payment::where('gateway', $payment->gateway)->where('gateway_txn_id', $gatewayTxnId)->where('id', '!=', $payment->id)->exists()) {
                $order->log('payment', 'Gateway transaction '.$gatewayTxnId.' was already used for another payment. Not accepted.');

                return $order;
            }

            $payment->update(['status' => 'success', 'gateway_txn_id' => $gatewayTxnId, 'method' => $method, 'verified_at' => now(), 'message' => $note]);
            $order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $payment->gateway,
                'status' => $order->needs_shipping ? 'processing' : 'completed',
            ]);
            $order->log('payment', 'Payment confirmed via '.Gateways::label($payment->gateway).($gatewayTxnId ? ' (transaction '.$gatewayTxnId.')' : '').($method ? ', '.$method : '').'.');

            $this->reduceStock($order);
            if ($order->coupon_code) {
                Coupon::where('code', $order->coupon_code)->increment('used_count');
            }
            $this->grantDownloads($order);
            DB::afterCommit(fn () => $this->email($order->fresh(), 'paid'));

            return $order;
        });
    }

    protected function reduceStock(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            $p = Product::lockForUpdate()->find($item->product_id);
            if (! $p) {
                continue;
            }
            if ($item->variant_key && ($v = $p->variant($item->variant_key)) && isset($v['stock']) && $v['stock'] !== '' && $v['stock'] !== null) {
                $p->variants = array_map(fn ($x) => ($x['key'] ?? null) === $item->variant_key ? array_merge($x, ['stock' => max(0, (int) $x['stock'] - $item->qty)]) : $x, $p->variants);
                $p->save();
            } elseif ($p->manage_stock) {
                $p->update(['stock' => max(0, (int) $p->stock - $item->qty)]);
            }
        }
    }

    public function grantDownloads(Order $order): void
    {
        foreach ($order->items()->with('product')->get() as $item) {
            $p = $item->product;
            if (! $p) {
                continue;
            }
            foreach ($p->filesFor($item->variant_key) as $f) {
                if ($order->downloads()->where('order_item_id', $item->id)->where('file_key', $f['key'])->exists()) {
                    continue;
                }
                $limit = $p->download_limit ?: store('download_limit');
                $days = $p->download_expiry_days ?: store('download_expiry_days');
                $order->downloads()->create([
                    'token' => Str::random(48), 'order_item_id' => $item->id, 'user_id' => $order->user_id, 'product_id' => $p->id,
                    'file_key' => $f['key'], 'file_name' => $f['name'] ?: basename($f['path']),
                    'downloads_remaining' => $limit !== null && $limit !== '' && (int) $limit > 0 ? (int) $limit : null,
                    'expires_at' => $days !== null && $days !== '' && (int) $days > 0 ? now()->addDays((int) $days) : null,
                ]);
            }
        }
    }

    /** Admin status change. Refund/cancel removes download access. */
    public function setStatus(Order $order, string $status, ?string $note = null): void
    {
        $old = $order->status;
        $data = ['status' => $status];
        if ($status === 'refunded') {
            $data['payment_status'] = 'refunded';
        }
        $order->update($data);
        if (in_array($status, ['refunded', 'cancelled', 'failed'], true)) {
            $order->downloads()->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }
        $order->log('status', 'Status changed from '.(Order::STATUSES[$old] ?? $old).' to '.(Order::STATUSES[$status] ?? $status).'.'.($note ? ' '.$note : ''));
        if (in_array($status, ['completed', 'cancelled', 'refunded'], true) && $old !== $status) {
            $this->email($order->fresh(), 'status');
        }
    }

    /** Admin confirms a manual payment (bank transfer, bKash personal …) after checking it. */
    public function confirmManual(Order $order, ?string $note = null): Order
    {
        $payment = $order->payments()->where('status', '!=', 'success')->first() ?? $this->newPayment($order, $order->payment_method ?: 'manual_bank');

        return $this->markPaid($payment, null, null, 'Manual', 'Confirmed by admin'.($note ? ': '.$note : ''));
    }

    /** Customer emails (only sent when SMTP is configured in Admin → Settings → Email). */
    public function email(Order $order, string $type): bool
    {
        $tpl = ['subject' => setting('store_emails.'.$type.'_subject'), 'body' => setting('store_emails.'.$type.'_body')];
        $defaults = self::emailDefaults()[$type] ?? null;
        if (! $defaults || ! $order->email() || ! SiteMail::configured()) {
            return false;
        }
        $subject = ($tpl['subject'] ?? '') ?: $defaults['subject'];
        $body = ($tpl['body'] ?? '') ?: $defaults['body'];

        $downloads = $order->downloads()->whereNull('revoked_at')->get()->map(fn ($d) => '- '.$d->file_name.': '.$d->link())->implode("\n");
        $items = $order->items()->get()->map(fn ($i) => '- '.$i->name.($i->variant_name ? ' ('.$i->variant_name.')' : '').' × '.$i->qty.' — '.money($i->total, $order->currency))->implode("\n");
        $gw = Gateways::config($order->payment_method);
        $vars = [
            '{name}' => $order->customerName(), '{order_number}' => $order->number, '{order_link}' => $order->link(),
            '{total}' => money($order->total, $order->currency), '{items}' => $items,
            '{downloads}' => $downloads ?: '(no downloads in this order)',
            '{status}' => Order::STATUSES[$order->status] ?? $order->status,
            '{payment_instructions}' => (string) ($gw['instructions'] ?? ''),
            '{site_name}' => (string) setting('site.name', config('app.name')),
        ];
        $ok = SiteMail::send($order->email(), strtr($subject, $vars), strtr($body, $vars));
        $order->log('email', ($ok ? 'Email sent: ' : 'Email could not be sent: ').strtr($subject, $vars));

        return $ok;
    }

    public static function emailDefaults(): array
    {
        return [
            'received' => [
                'subject' => 'Order {order_number} received — {site_name}',
                'body' => "Hi {name},\n\nThank you for your order {order_number}.\n\n{items}\n\nTotal: {total}\n\n{payment_instructions}\n\nYou can see your order and pay for it here:\n{order_link}\n\n{site_name}",
            ],
            'paid' => [
                'subject' => 'Payment received for order {order_number}',
                'body' => "Hi {name},\n\nWe have received your payment for order {order_number}.\n\n{items}\n\nTotal: {total}\n\nYour downloads:\n{downloads}\n\nOrder details:\n{order_link}\n\nThank you,\n{site_name}",
            ],
            'status' => [
                'subject' => 'Order {order_number}: {status}',
                'body' => "Hi {name},\n\nThe status of your order {order_number} is now: {status}.\n\nOrder details:\n{order_link}\n\n{site_name}",
            ],
        ];
    }
}
