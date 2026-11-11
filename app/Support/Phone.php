<?php

namespace App\Support;

/** One stored form for phone numbers, so "+880 1711-000000", "8801711000000" and "01711000000" all match. */
class Phone
{
    public static function normalize(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }
        // Bangladesh mobile numbers: keep the local 01XXXXXXXXX form
        if (str_starts_with($digits, '8801') && strlen($digits) === 13) {
            return '0'.substr($digits, 3);
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (preg_match('/^01\d{9}$/', $digits)) {
            return $digits;
        }

        return str_starts_with($raw, '+') || strlen($digits) > 11 ? '+'.$digits : $digits;
    }

    public static function looksLikePhone(string $value): bool
    {
        return ! str_contains($value, '@') && strlen(preg_replace('/\D+/', '', $value)) >= 7;
    }
}
