<?php

namespace App\Store;

use App\Models\Setting;
use App\Store\Gateways\AamarPay;
use App\Store\Gateways\CustomGateway;
use App\Store\Gateways\Eps;
use App\Store\Gateways\Gateway;
use App\Store\Gateways\Manual;
use App\Store\Gateways\PayStation;
use Illuminate\Support\Facades\Crypt;

/**
 * Registry of payment gateways. Settings live in the "payment_gateways" setting:
 *   [key => ['enabled' => bool, 'title' => …, 'mode' => 'sandbox'|'live', <driver fields>…]]
 * Secret fields are stored encrypted.
 *
 * To add a gateway in code: create a class in app/Store/Gateways extending Gateway
 * and add it to DRIVERS below. Simple redirect gateways can be configured without
 * code through "Custom gateway".
 */
class Gateways
{
    public const DRIVERS = [
        'paystation' => PayStation::class,
        'aamarpay' => AamarPay::class,
        'eps' => Eps::class,
        'manual_bank' => Manual::class,
        'manual_mobile' => Manual::class,
        'custom_1' => CustomGateway::class,
        'custom_2' => CustomGateway::class,
    ];

    public static function driver(string $key): ?Gateway
    {
        $class = self::DRIVERS[$key] ?? null;

        return $class ? new $class($key, self::config($key)) : null;
    }

    /** Stored settings of one gateway with secrets decrypted. */
    public static function config(?string $key): array
    {
        if (! $key || ! isset(self::DRIVERS[$key])) {
            return [];
        }
        $all = (array) Setting::get('payment_gateways', []);
        $cfg = (array) ($all[$key] ?? []);
        $class = self::DRIVERS[$key];
        foreach ($class::fields($key) as $f) {
            if (in_array($f['type'] ?? '', ['secret', 'secret_text'], true) && filled($cfg[$f['key']] ?? null)) {
                $cfg[$f['key']] = rescue(fn () => Crypt::decryptString($cfg[$f['key']]), '', false);
            }
        }

        return $cfg + $class::defaults($key);
    }

    public static function save(string $key, array $values): void
    {
        $all = (array) Setting::get('payment_gateways', []);
        $old = (array) ($all[$key] ?? []);
        $class = self::DRIVERS[$key];
        $out = [];
        foreach ($class::fields($key) as $f) {
            $k = $f['key'];
            $v = $values[$k] ?? null;
            if (($f['type'] ?? '') === 'heading') {
                continue;
            }
            if (in_array($f['type'] ?? '', ['secret', 'secret_text'], true)) {
                $out[$k] = blank($v) ? ($old[$k] ?? null) : Crypt::encryptString(trim((string) $v));
            } elseif (($f['type'] ?? '') === 'checkbox') {
                $out[$k] = (bool) $v;
            } else {
                $out[$k] = is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : $v;
            }
        }
        $all[$key] = $out;
        Setting::put('payment_gateways', $all);
    }

    /** @return array<string, Gateway> enabled + configured gateways that accept this currency */
    public static function available(string $currency): array
    {
        $out = [];
        foreach (array_keys(self::DRIVERS) as $key) {
            $g = self::driver($key);
            if ($g && $g->enabled() && $g->configured() && $g->supports($currency)) {
                $out[$key] = $g;
            }
        }
        uasort($out, fn ($a, $b) => (int) ($a->config['sort'] ?? 0) <=> (int) ($b->config['sort'] ?? 0));

        return $out;
    }

    public static function label(?string $key): string
    {
        if (! $key) {
            return '—';
        }
        $g = self::driver($key);

        return $g ? $g->title() : $key;
    }
}
