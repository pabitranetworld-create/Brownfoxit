<?php

namespace App\Store;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/** Cart stored in the database and remembered with a cookie for 60 days (also linked to the customer account). */
class CartService
{
    public const COOKIE = 'bfi_cart';

    protected ?Cart $cart = null;

    public function get(bool $create = false): ?Cart
    {
        if ($this->cart) {
            return $this->cart;
        }
        $user = auth()->user();
        $token = (string) request()->cookie(self::COOKIE);
        $cart = $token !== '' ? Cart::where('token', $token)->first() : null;

        if ($user && ! $user->isAdmin()) {
            $mine = Cart::where('user_id', $user->id)->latest('id')->first();
            if ($cart && $mine && $cart->id !== $mine->id) {
                // merge the guest cart into the account cart after login
                foreach ($cart->items as $it) {
                    $ex = $mine->items()->where('product_id', $it->product_id)->where('variant_key', $it->variant_key)->first();
                    $ex ? $ex->increment('qty', $it->qty) : $mine->items()->create($it->only('product_id', 'variant_key', 'qty'));
                }
                $cart->delete();
                $cart = $mine;
            } elseif (! $cart && $mine) {
                $cart = $mine;
            } elseif ($cart && ! $cart->user_id) {
                $cart->update(['user_id' => $user->id]);
            }
        }

        if (! $cart && $create) {
            $cart = Cart::create(['token' => Str::random(48), 'user_id' => $user && ! $user->isAdmin() ? $user->id : null]);
        }
        if ($cart && $cart->token !== $token) {
            Cookie::queue(self::COOKIE, $cart->token, 60 * 24 * 60, null, null, request()->isSecure(), true, false, 'lax');
        }

        return $this->cart = $cart;
    }

    /** @return string|null error message */
    public function add(Product $product, ?string $variant, int $qty): ?string
    {
        if ($product->status !== 'published') {
            return 'This product is not available.';
        }
        if ($product->hasVariants() && ! $product->variant($variant)) {
            return 'Please choose an option.';
        }
        if (! $product->hasVariants()) {
            $variant = null;
        }
        $qty = max(1, min(99, $qty));
        $cart = $this->get(true);
        $item = $cart->items()->where('product_id', $product->id)->where('variant_key', $variant)->first();
        $newQty = ($item->qty ?? 0) + $qty;
        if ($product->isDigital()) {
            $newQty = 1; // a licence is bought once per order
        }
        if (! $product->inStock($variant, $newQty)) {
            return 'Sorry, not enough stock is available.';
        }
        $item ? $item->update(['qty' => $newQty]) : $cart->items()->create(['product_id' => $product->id, 'variant_key' => $variant, 'qty' => $newQty]);
        $cart->touch();

        return null;
    }

    public function update(int $itemId, int $qty): ?string
    {
        $item = $this->item($itemId);
        if (! $item) {
            return 'That item is no longer in your cart.';
        }
        if ($qty <= 0) {
            $item->delete();

            return null;
        }
        $item->loadMissing('product');
        $qty = $item->product->isDigital() ? 1 : min(99, $qty);
        if (! $item->product->inStock($item->variant_key, $qty)) {
            return 'Sorry, only '.$item->product->stockLeft($item->variant_key).' left in stock.';
        }
        $item->update(['qty' => $qty]);

        return null;
    }

    public function remove(int $itemId): void
    {
        $this->item($itemId)?->delete();
    }

    protected function item(int $id): ?CartItem
    {
        $cart = $this->get();

        return $cart ? $cart->items()->with('product')->find($id) : null;
    }

    public function setCoupon(?string $code): void
    {
        $this->get(true)->update(['coupon_code' => $code ? trim($code) : null]);
    }

    /** Lines with live prices; unavailable products are removed automatically. */
    public function lines(): array
    {
        $cart = $this->get();
        if (! $cart) {
            return [];
        }
        $lines = [];
        foreach ($cart->items()->with('product.category')->get() as $it) {
            $p = $it->product;
            if (! $p || $p->status !== 'published' || ($p->hasVariants() && ! $p->variant($it->variant_key))) {
                $it->delete();

                continue;
            }
            $lines[] = ['id' => $it->id, 'product' => $p, 'variant' => $it->variant_key, 'qty' => (int) $it->qty];
        }

        return $lines;
    }

    public function totals(): array
    {
        return Pricing::compute($this->lines(), $this->get()?->coupon_code);
    }

    public function count(): int
    {
        $cart = $this->get();

        return $cart ? (int) $cart->items()->sum('qty') : 0;
    }

    public function clear(): void
    {
        $cart = $this->get();
        if ($cart) {
            $cart->items()->delete();
            $cart->update(['coupon_code' => null]);
        }
    }
}
