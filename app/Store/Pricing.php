<?php

namespace App\Store;

use App\Models\Coupon;
use App\Models\Product;

/**
 * The ONLY place where order amounts are calculated. Prices always come from the
 * database, never from the browser.
 */
class Pricing
{
    /**
     * @param  array<int, array{product: Product, variant: ?string, qty: int, id?: int}>  $lines
     */
    public static function compute(array $lines, ?string $couponCode = null): array
    {
        $out = [];
        $subtotal = 0.0;
        $needsShipping = false;
        $weight = 0.0;
        foreach ($lines as $l) {
            $p = $l['product'];
            $price = $p->currentPrice($l['variant']);
            $total = round($price * $l['qty'], 2);
            $subtotal += $total;
            if (! $p->isDigital()) {
                $needsShipping = true;
                $weight += (float) $p->weight * $l['qty'];
            }
            $v = $l['variant'] ? $p->variant($l['variant']) : null;
            $out[] = $l + ['price' => $price, 'total' => $total, 'variant_name' => $v['name'] ?? null, 'regular' => $p->prices($l['variant'])[0]];
        }
        $subtotal = round($subtotal, 2);

        $discount = 0.0;
        $couponError = null;
        $coupon = null;
        if ($couponCode && store('coupons_enabled', true)) {
            $coupon = Coupon::whereRaw('LOWER(code) = ?', [mb_strtolower($couponCode)])->first();
            if (! $coupon) {
                $couponError = 'This coupon code does not exist.';
            } elseif ($e = $coupon->problem($subtotal)) {
                $couponError = $e;
                $coupon = null;
            } else {
                $discount = $coupon->discountFor($subtotal);
            }
        }

        $shipping = 0.0;
        if ($needsShipping) {
            $free = store('free_shipping_over');
            $shipping = ($free !== null && $free !== '' && $subtotal - $discount >= (float) $free) ? 0.0 : (float) store('shipping_flat', 0);
        }

        $rate = (float) store('tax_rate', 0);
        $tax = $rate > 0 ? round(($subtotal - $discount + $shipping) * $rate / 100, 2) : 0.0;

        return [
            'lines' => $out,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => round($shipping, 2),
            'tax' => $tax,
            'total' => round($subtotal - $discount + $shipping + $tax, 2),
            'needs_shipping' => $needsShipping,
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'currency' => store('currency', 'BDT'),
        ];
    }
}
