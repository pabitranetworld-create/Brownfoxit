<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Store\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function show(): View
    {
        return view('shop.cart', ['t' => $this->cart->totals(), 'coupon' => $this->cart->get()?->coupon_code]);
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer'], 'variant' => ['nullable', 'string', 'max:40'], 'qty' => ['nullable', 'integer', 'min:1', 'max:99'], 'buy_now' => ['nullable']]);
        $product = Product::find($data['product_id']);
        $error = $product ? $this->cart->add($product, $data['variant'] ?? null, (int) ($data['qty'] ?? 1)) : 'This product is not available.';

        if ($request->expectsJson()) {
            return response()->json($error ? ['ok' => false, 'message' => $error] : ['ok' => true, 'count' => $this->cart->count(), 'message' => '“'.$product->name.'” was added to your cart.', 'cart_url' => route('cart.show'), 'checkout_url' => route('checkout.show')], $error ? 422 : 200);
        }
        if ($error) {
            return back()->withErrors(['cart' => $error]);
        }

        return $request->filled('buy_now') ? redirect()->route('checkout.show') : redirect()->route('cart.show')->with('status', '“'.$product->name.'” was added to your cart.');
    }

    public function update(Request $request): RedirectResponse
    {
        $errors = [];
        foreach ((array) $request->input('qty', []) as $id => $qty) {
            if ($e = $this->cart->update((int) $id, (int) $qty)) {
                $errors[] = $e;
            }
        }

        return redirect()->route('cart.show')->withErrors($errors ? ['cart' => implode(' ', $errors)] : [])->with($errors ? [] : ['status' => 'Cart updated.']);
    }

    public function remove(int $item): RedirectResponse
    {
        $this->cart->remove($item);

        return redirect()->route('cart.show')->with('status', 'Item removed.');
    }

    public function coupon(Request $request): RedirectResponse
    {
        if ($request->boolean('remove')) {
            $this->cart->setCoupon(null);

            return back()->with('status', 'Coupon removed.');
        }
        $code = trim((string) $request->input('code'));
        $this->cart->setCoupon($code ?: null);
        $t = $this->cart->totals();
        if ($t['coupon_error']) {
            $this->cart->setCoupon(null);

            return back()->withErrors(['coupon' => $t['coupon_error']]);
        }

        return back()->with('status', 'Coupon applied.');
    }
}
