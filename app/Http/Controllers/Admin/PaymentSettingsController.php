<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Store\Gateways;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Payment gateways: enable/disable, credentials, test/live mode, connection test. */
class PaymentSettingsController extends Controller
{
    public function index(): View
    {
        $list = [];
        foreach (array_keys(Gateways::DRIVERS) as $key) {
            $list[$key] = Gateways::driver($key);
        }

        return view('admin.payments.index', ['gateways' => $list, 'currency' => store('currency', 'BDT')]);
    }

    public function edit(string $gateway): View
    {
        $g = Gateways::driver($gateway);
        abort_unless($g, 404);

        return view('admin.payments.edit', ['g' => $g, 'key' => $gateway, 'fields' => $g::fields($gateway), 'help' => $g::help()]);
    }

    public function update(Request $request, string $gateway): RedirectResponse
    {
        abort_unless(isset(Gateways::DRIVERS[$gateway]), 404);
        $values = (array) $request->input('g', []);
        foreach ($values as $k => $v) {
            if (is_string($v) && mb_strlen($v) > 20000) {
                return back()->withErrors(['g' => 'একটা ঘরের লেখা খুব বড়।']);
            }
        }
        Gateways::save($gateway, $values);
        $g = Gateways::driver($gateway);
        $msg = 'সেভ হয়েছে।';
        if ($g->enabled() && ! $g->configured()) {
            $msg .= ' সতর্কতা: চালু করা আছে কিন্তু দরকারি তথ্য পূরণ হয়নি — চেকআউটে দেখাবে না।';
        } elseif ($g->enabled() && ! $g->supports(store('currency', 'BDT'))) {
            $msg .= ' সতর্কতা: এই গেটওয়ে দোকানের কারেন্সি ('.store('currency', 'BDT').') নেয় না — চেকআউটে দেখাবে না।';
        }

        return redirect()->route('admin.payments.edit', $gateway)->with('status', $msg);
    }

    public function toggle(string $gateway): RedirectResponse
    {
        abort_unless(isset(Gateways::DRIVERS[$gateway]), 404);
        $cfg = Gateways::config($gateway);
        $cfg['enabled'] = ! ($cfg['enabled'] ?? false);
        // keep secrets: blank secret values mean "unchanged"
        foreach (Gateways::driver($gateway)::fields($gateway) as $f) {
            if (in_array($f['type'] ?? '', ['secret', 'secret_text'], true)) {
                $cfg[$f['key']] = '';
            }
        }
        Gateways::save($gateway, $cfg);

        return back()->with('status', Gateways::label($gateway).($cfg['enabled'] ? ' চালু হয়েছে।' : ' বন্ধ হয়েছে।'));
    }

    public function test(string $gateway): RedirectResponse
    {
        $g = Gateways::driver($gateway);
        abort_unless($g, 404);
        [$ok, $message] = $g->test();

        return back()->with($ok ? 'status' : 'test_error', $message)->withErrors($ok ? [] : ['test' => $message]);
    }
}
