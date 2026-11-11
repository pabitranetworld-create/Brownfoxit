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

    public function coupon(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->boolean('remove')) {
            $this->cart->setCoupon(null);

            return $this->couponReply($request, true, 'Coupon removed.');
        }
        $code = trim((string) $request->input('code'));
        if ($code === '') {
            return $this->couponReply($request, false, 'Please enter a coupon code.');
        }
        $this->cart->setCoupon($code);
        $t = $this->cart->totals();
        if ($t['coupon_error']) {
            $this->cart->setCoupon(null);

            return $this->couponReply($request, false, $t['coupon_error']);
        }

        return $this->couponReply($request, true, 'Coupon applied.');
    }

    /** JSON for the checkout page (totals refresh without reloading), a redirect otherwise. */
    protected function couponReply(Request $request, bool $ok, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            $t = $this->cart->totals();

            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'coupon' => $t['coupon']?->code,
                'totals_html' => view('shop.partials.totals', ['t' => $t])->render(),
                'total' => money($t['total']),
            ], $ok ? 200 : 422);
        }

        return $ok ? back()->with('status', $message) : back()->withInput()->withErrors(['coupon' => $message]);
    }
}
