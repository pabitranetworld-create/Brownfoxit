<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Store\Gateways;
use App\Store\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Gateways send the customer back here, and some also notify us in the background (IPN).
 * Whatever the browser says, the result is ALWAYS confirmed with the gateway server-to-server
 * (Gateway::verify) before an order is marked as paid. Repeated callbacks are harmless.
 */
class PaymentController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function return(Request $request, string $gateway, string $ref, string $result): RedirectResponse
    {
        $payment = Payment::where('gateway', $gateway)->where('reference', $ref)->with('order')->firstOrFail();
        $order = $payment->order;

        if ($payment->status !== 'success') {
            $v = $this->check($payment, $request);
            if ($v['status'] === 'success') {
                $order = $this->orders->markPaid($payment, $v['txn_id'] ?? null, $v['amount'] ?? null, $v['method'] ?? null);
            } elseif ($v['status'] === 'pending' && $result === 'success' && ! in_array(strtolower((string) $request->input('status')), ['failed', 'canceled', 'cancelled', 'cancel'], true)) {
                $payment->update(['status' => 'pending', 'message' => $v['message'] ?? null]);
                $order->update(['payment_status' => 'pending']);
                $order->log('payment', Gateways::label($gateway).' returned, but the payment is not confirmed yet ('.($v['message'] ?? 'pending').').');
            } else {
                $status = $result === 'cancel' || $v['status'] === 'cancelled' || in_array(strtolower((string) $request->input('status')), ['canceled', 'cancelled', 'cancel'], true) ? 'cancelled' : 'failed';
                $result = $status === 'cancelled' ? 'cancel' : 'fail';
                $payment->update(['status' => $status, 'message' => $v['message'] ?? null]);
                if (! $order->isPaid()) {
                    $order->update(['payment_status' => 'unpaid']);
                }
                $order->log('payment', 'Payment '.$status.' at '.Gateways::label($gateway).'.');
            }
        }
        $order->refresh();

        if ($order->isPaid()) {
            return redirect($order->link())->with('status', 'Payment received — thank you! Your order is confirmed.');
        }
        if ($payment->fresh()->status === 'pending') {
            return redirect($order->link())->with('status', 'We are waiting for the payment confirmation from '.Gateways::label($gateway).'. This page will show the result as soon as it arrives.');
        }

        return redirect($order->link())->withErrors(['payment' => $result === 'cancel' ? 'The payment was cancelled. You can try again below.' : 'The payment did not go through. You can try again or choose another method.']);
    }

    /** Background notification from the gateway. */
    public function ipn(Request $request, string $gateway): Response
    {
        $ref = null;
        foreach (['invoice_number', 'mer_txnid', 'tran_id', 'merchantTransactionId', 'MerchantTransactionId', 'reference', 'ref'] as $k) {
            if (filled($request->input($k))) {
                $ref = (string) $request->input($k);
                break;
            }
        }
        $param = Gateways::config($gateway)['callback_ref_param'] ?? null;
        if ($param && filled($request->input($param))) {
            $ref = (string) $request->input($param);
        }
        $payment = $ref ? Payment::where('gateway', $gateway)->where('reference', $ref)->first() : null;
        if (! $payment) {
            return response('unknown transaction', 404);
        }
        if ($payment->status !== 'success') {
            $v = $this->check($payment, $request);
            if ($v['status'] === 'success') {
                $this->orders->markPaid($payment, $v['txn_id'] ?? null, $v['amount'] ?? null, $v['method'] ?? null);
            }
        }

        return response('OK', 200);
    }

    protected function check(Payment $payment, Request $request): array
    {
        $driver = Gateways::driver($payment->gateway);
        if (! $driver) {
            return ['status' => 'failed', 'message' => 'Unknown gateway'];
        }
        $v = $driver->verify($payment, $request);
        $payment->save(); // stores the gateway answer kept by remember()
        Log::info('Payment verify', ['gateway' => $payment->gateway, 'ref' => $payment->reference, 'status' => $v['status'] ?? null]);

        return $v + ['status' => 'pending'];
    }
}
