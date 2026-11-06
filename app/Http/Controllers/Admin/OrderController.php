<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadPermission;
use App\Models\Order;
use App\Store\Gateways;
use App\Store\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));
        $items = Order::query()->withCount('items')
            ->when(isset(Order::STATUSES[$status]), fn ($w) => $w->where('status', $status))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('number', 'like', "%{$q}%")->orWhere('billing->email', 'like', "%{$q}%")->orWhere('billing->name', 'like', "%{$q}%")->orWhere('manual_reference', 'like', "%{$q}%")))
            ->latest('id')->paginate(25)->withQueryString();

        $paid = Order::where('payment_status', 'paid');

        return view('admin.orders.index', [
            'items' => $items, 'status' => $status, 'q' => $q,
            'counts' => Order::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
            'stats' => [
                'today' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfDay())->sum('total'),
                'month' => (float) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
                'all' => (float) (clone $paid)->sum('total'),
                'paid_count' => (clone $paid)->count(),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'payments', 'downloads', 'events', 'user']);

        return view('admin.orders.show', ['order' => $order, 'gatewayLabel' => Gateways::label($order->payment_method)]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['admin_note'] !== $order->admin_note) {
            $order->update(['admin_note' => $data['admin_note']]);
        }
        if ($data['status'] !== $order->status) {
            if (in_array($data['status'], ['completed', 'processing'], true) && ! $order->isPaid()) {
                return back()->withErrors(['status' => 'পেমেন্ট নিশ্চিত না করে Completed/Processing করা যাবে না। আগে "পেমেন্ট নিশ্চিত করুন" ব্যবহার করুন।']);
            }
            $this->orders->setStatus($order, $data['status'], $data['note'] ?? null);
        } elseif (filled($data['note'] ?? null)) {
            $order->log('note', $data['note']);
        }

        return back()->with('status', 'অর্ডার আপডেট হয়েছে।');
    }

    /** Manual payments (bank, bKash personal …): admin checked the money arrived. */
    public function confirmPayment(Request $request, Order $order): RedirectResponse
    {
        if ($order->isPaid()) {
            return back()->with('status', 'এই অর্ডার আগেই paid।');
        }
        $this->orders->confirmManual($order, $request->input('note'));

        return back()->with('status', 'পেমেন্ট নিশ্চিত হয়েছে — ডাউনলোড চালু হয়েছে ও গ্রাহককে ইমেইল পাঠানো হয়েছে (SMTP থাকলে)।');
    }

    /** Re-check an online payment with the gateway (if the customer closed the browser too early). */
    public function recheck(Order $order): RedirectResponse
    {
        $payment = $order->payments()->whereIn('status', ['pending', 'initiated', 'failed', 'cancelled'])->first();
        $driver = $payment ? Gateways::driver($payment->gateway) : null;
        if (! $payment || ! $driver) {
            return back()->withErrors(['payment' => 'যাচাই করার মতো অনলাইন পেমেন্ট নেই।']);
        }
        $v = $driver->verify($payment);
        $payment->save();
        if (($v['status'] ?? '') === 'success') {
            $this->orders->markPaid($payment, $v['txn_id'] ?? null, $v['amount'] ?? null, $v['method'] ?? null);

            return back()->with('status', 'গেটওয়ে পেমেন্ট নিশ্চিত করেছে — অর্ডার paid।');
        }
        $order->log('payment', 'Admin re-checked the payment: '.($v['status'] ?? 'unknown').($v['message'] ?? '' ? ' — '.$v['message'] : ''));

        return back()->with('status', 'গেটওয়ের উত্তর: '.($v['status'] ?? 'unknown').(($v['message'] ?? '') ? ' — '.$v['message'] : ''));
    }

    public function resend(Order $order): RedirectResponse
    {
        $ok = $this->orders->email($order, $order->isPaid() ? 'paid' : 'received');

        return back()->with('status', $ok ? 'ইমেইল আবার পাঠানো হয়েছে।' : 'ইমেইল পাঠানো যায়নি — Settings → ইমেইল (SMTP) দেখুন।');
    }

    public function download(Request $request, Order $order, DownloadPermission $download): RedirectResponse
    {
        abort_unless($download->order_id === $order->id, 404);
        match ($request->input('action')) {
            'revoke' => $download->update(['revoked_at' => now()]),
            'restore' => $download->update(['revoked_at' => null]),
            'extend' => $download->update(['expires_at' => now()->addDays(30), 'downloads_remaining' => $download->downloads_remaining !== null ? $download->downloads_remaining + 5 : null, 'revoked_at' => null]),
            default => null,
        };
        $order->log('download', 'Admin '.$request->input('action').': '.$download->file_name);

        return back()->with('status', 'ডাউনলোড আপডেট হয়েছে।');
    }

    public function grant(Order $order): RedirectResponse
    {
        abort_unless($order->isPaid(), 422);
        $this->orders->grantDownloads($order);

        return back()->with('status', 'প্রোডাক্টের বর্তমান ফাইলগুলোর ডাউনলোড যোগ হয়েছে।');
    }
}
