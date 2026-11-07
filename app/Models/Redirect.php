<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    public const CACHE = 'redirect_map';

    protected $fillable = ['from_path', 'from_hash', 'to_url', 'status_code', 'is_active', 'source', 'hits', 'last_hit_at', 'notes'];

    protected $casts = ['is_active' => 'boolean', 'last_hit_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (Redirect $r) {
            $r->from_path = self::normalize($r->from_path);
            $r->from_hash = md5($r->from_path);
        });
        static::saved(fn () => Cache::forget(self::CACHE));
        static::deleted(fn () => Cache::forget(self::CACHE));
    }

    /** "/Old-Page/?utm=x" → "/old-page" (lower case, no trailing slash, no query, decoded). */
    public static function normalize(?string $path): string
    {
        $path = trim((string) $path);
        if (preg_match('#^https?://#i', $path)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }
        $path = strtok($path, '?#') ?: '/';
        $path = '/'.ltrim(rawurldecode($path), '/');
        $path = rtrim(mb_strtolower($path), '/');

        return $path === '' ? '/' : $path;
    }

    /** All active redirects as [from_path => [to, code, id]] (cached). */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE, fn () => rescue(fn () => static::where('is_active', true)->get(['id', 'from_path', 'to_url', 'status_code'])
            ->mapWithKeys(fn ($r) => [$r->from_path => [$r->to_url, (int) $r->status_code, $r->id]])->all(), [], false));
    }

    /** Where would $to finally land, following other redirects? Returns [finalUrl, hops, loop]. */
    public static function follow(string $to, ?string $ignoreFrom = null): array
    {
        $map = static::map();
        $seen = $ignoreFrom ? [self::normalize($ignoreFrom) => true] : [];
        $hops = 0;
        while (! preg_match('#^https?://#i', $to) || self::isOwnUrl($to)) {
            $p = self::normalize($to);
            if (isset($seen[$p])) {
                return [$to, $hops, true];
            }
            $seen[$p] = true;
            if (! isset($map[$p])) {
                break;
            }
            $to = $map[$p][0];
            if (++$hops > 10) {
                return [$to, $hops, true];
            }
        }

        return [$to, $hops, false];
    }

    public static function isOwnUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host && preg_replace('/^www\./', '', strtolower($host)) === preg_replace('/^www\./', '', strtolower(request()->getHost()));
    }
}
