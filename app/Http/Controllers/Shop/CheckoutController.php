<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Store\CartService;
use App\Store\Gateways;
use App\Store\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cart, protected OrderService $orders) {}

    public function show(): View|RedirectResponse
    {
        $t = $this->cart->totals();
        if (! $t['lines']) {
            return redirect()->route('cart.show');
        }
        if (! store('guest_checkout', true) && ! auth()->check()) {
            return redirect()->guest(route('account.login'))->with('status', 'Please log in or create an account to check out.');
        }

        return view('shop.checkout', ['t' => $t, 'gateways' => Gateways::available($t['currency']), 'user' => auth()->user()]);
    }

    public function place(Request $request): RedirectResponse|Response
    {
        $t = $this->cart->totals();
        if (! $t['lines']) {
            return redirect()->route('cart.show');
        }
        $gateways = Gateways::available($t['currency']);
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:filter', 'max:190'],
            'phone' => [store('require_phone', true) ? 'required' : 'nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:190'],
            'address' => [$t['needs_shipping'] ? 'required' : 'nullable', 'string', 'max:255'],
            'city' => [$t['needs_shipping'] ? 'required' : 'nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:2000'],
            'gateway' => ['required', Rule::in(array_keys($gateways))],
            'reference' => ['nullable', 'string', 'max:120'],
            'terms' => [filled(store('terms_url')) ? 'accepted' : 'nullable'],
            'create_account' => ['nullable'],
            'password' => ['nullable', 'required_with:create_account', 'string', 'min:8', 'max:100'],
        ];
        $data = $request->validate($rules, [
            'gateway.required' => 'Please choose a payment method.', 'gateway.in' => 'Please choose a payment method.',
            'terms.accepted' => 'Please accept the terms to continue.',
        ]);
        $gw = $gateways[$data['gateway']];
        if ($gw->asksReference() && blank($data['reference'] ?? null)) {
            return back()->withInput()->withErrors(['reference' => 'Please enter the '.strtolower((string) ($gw->config['reference_label'] ?? 'reference')).' of your payment.']);
        }

        // optional account creation for guests
        if (! auth()->check() && $request->boolean('create_account')) {
            if (\App\Models\User::where('email', $data['email'])->exists()) {
                return back()->withInput()->withErrors(['email' => 'An account with this email already exists. Please log in first.']);
            }
            $user = \App\Models\User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role' => 'customer', 'phone' => ($ph = \App\Support\Phone::normalize($data['phone'] ?? null)) && ! \App\Models\User::where('phone', $ph)->exists() ? $ph : null]);
            auth()->login($user);
            $request->session()->regenerate();
        }

        $billing = array_intersect_key($data, array_flip(['name', 'email', 'phone', 'company', 'address', 'city', 'postcode', 'country']));
        try {
            $order = $this->orders->createFromCart($billing, $t['needs_shipping'] ? $billing : null, $data['note'] ?? null, $gw->key);
        } catch (RuntimeException $e) {
            return redirect()->route('cart.show')->withErrors(['cart' => $e->getMessage()]);
        }

        return $this->startPayment($order, $gw->key, $data['reference'] ?? null);
    }

    /** Hand the customer to the gateway (or show manual instructions). */
    protected function startPayment(Order $order, string $gatewayKey, ?string $reference): RedirectResponse|Response
    {
        $gw = Gateways::driver($gatewayKey);
        $order->update(['payment_method' => $gatewayKey]);
        $payment = $this->orders->newPayment($order, $gatewayKey);
        $r = $gw->start($order->load('items'), $payment);
        $payment->save();

        if (! empty($r['manual'])) {
            $order->update(['status' => 'on_hold', 'payment_status' => 'pending', 'manual_reference' => $reference]);
            $payment->update(['status' => 'pending', 'gateway_txn_id' => null, 'message' => $reference ? 'Customer reference: '.$reference : null]);
            $order->log('payment', 'Customer chose '.$gw->title().($reference ? ' and entered reference '.$reference : '').'. Waiting for manual confirmation.');
            $this->orders->email($order, 'received');

            return redirect($order->link())->with('status', 'Thank you! Your order is placed and waiting for payment confirmation.');
        }
        if (! empty($r['redirect'])) {
            $payment->update(['status' => 'pending']);
            $order->update(['payment_status' => 'pending']);
            $order->log('payment', 'Customer sent to '.$gw->title().' to pay (reference '.$payment->reference.').');

            return redirect()->away($r['redirect']);
        }
        if (! empty($r['form'])) {
            $payment->update(['status' => 'pending']);
            $order->log('payment', 'Customer sent to '.$gw->title().' to pay (reference '.$payment->reference.').');

            return response()->view('shop.redirect-form', ['form' => $r['form'], 'gateway' => $gw->title()]);
        }
        $payment->update(['status' => 'failed', 'message' => $r['error'] ?? 'Could not start payment']);
        $order->log('payment', 'Payment could not be started: '.($r['error'] ?? 'unknown error'));

        return redirect($order->link())->withErrors(['payment' => ($r['error'] ?? 'The payment could not be started.').' You can try again or choose another payment method below.']);
    }

    public function order(Request $request, string $number): View
    {
        $order = Order::where('number', $number)->with(['items', 'downloads', 'payments'])->firstOrFail();
        $owner = auth()->check() && (auth()->id() === $order->user_id || auth()->user()->isAdmin());
        abort_unless($owner || hash_equals($order->access_key, (string) $request->query('key')), 404);

        return view('shop.order', ['order' => $order, 'gateways' => $order->canPay() ? Gateways::available($order->currency) : [], 'gw' => Gateways::driver((string) $order->payment_method)]);
    }

    /** Pay (again) for an unpaid order, e.g. after a cancelled payment. */
    public function pay(Request $request, string $number): RedirectResponse|Response
    {
        $order = Order::where('number', $number)->firstOrFail();
        $owner = auth()->check() && auth()->id() === $order->user_id;
        abort_unless($owner || hash_equals($order->access_key, (string) $request->input('key')), 404);
        if (! $order->canPay()) {
            return redirect($order->link())->withErrors(['payment' => 'This order cannot be paid again.']);
        }
        $gateways = Gateways::available($order->currency);
        $data = $request->validate(['gateway' => ['required', Rule::in(array_keys($gateways))], 'reference' => ['nullable', 'string', 'max:120']]);
        if ($gateways[$data['gateway']]->asksReference() && blank($data['reference'] ?? null)) {
            return redirect($order->link())->withErrors(['reference' => 'Please enter the transaction ID of your payment.']);
        }

        return $this->startPayment($order, $data['gateway'], $data['reference'] ?? null);
    }
}
