<?php

namespace App\Content;

/**
 * A stable hash of a page-builder layout. Used to recognise pages that are still exactly
 * as the installer created them (so a newer design may replace them) — a page you edited
 * in the builder gets a different hash and is never replaced.
 */
class Fingerprint
{
    public static function of(?array $layout): string
    {
        return md5(json_encode(self::norm($layout ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected static function norm(mixed $v): mixed
    {
        if (is_array($v)) {
            $v = array_map([self::class, 'norm'], $v);
            if (! array_is_list($v)) {
                ksort($v);
            }

            return $v;
        }
        if (is_bool($v) || $v === null) {
            return $v;
        }

        return (string) $v;
    }
}
